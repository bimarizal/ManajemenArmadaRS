<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogBbmResource\Pages;
use App\Models\Armada;
use App\Models\LogBbm;
use App\Support\Rupiah;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogBbmResource extends Resource
{
    protected static ?string $model = LogBbm::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Log BBM';

    protected static ?string $modelLabel = 'Log BBM';

    protected static ?string $pluralModelLabel = 'Log BBM';

    protected static ?int $navigationSort = 1;

    /** Driver hanya melihat riwayat pengisian miliknya sendiri. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['armada', 'driver']);
        $user = auth()->user();

        // Hanya Admin Garasi yang dapat melihat/memulihkan data terhapus.
        if ($user?->isAdminGarasi()) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $user?->isDriver() ? $query->where('driver_id', $user->id) : $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Pengisian')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('armada_id')
                        ->label('Armada')
                        ->relationship('armada', 'plat_nomor', modifyQueryUsing: fn (Builder $query) => $query->withoutTrashed()->where('status', '!=', 'Non-Aktif'))
                        ->getOptionLabelFromRecordUsing(fn (Armada $r) => "{$r->plat_nomor} — {$r->merk_tipe}")
                        ->searchable(['plat_nomor', 'merk_tipe'])
                        ->preload()
                        ->required()
                        ->live()
                        // Auto-fill odometer awal dari odometer terakhir armada.
                        ->afterStateUpdated(function ($state, Set $set, string $operation) {
                            if ($operation === 'create') {
                                $set('odometer_awal', Armada::whereKey($state)->value('odometer_saat_ini'));
                            }
                        }),

                    DateTimePicker::make('tgl_pengisian')
                        ->label('Tanggal & Jam Pengisian')
                        ->default(now())
                        ->maxDate(now()->addMinutes(5))
                        ->seconds(false)
                        ->required(),

                    TextInput::make('odometer_awal')
                        ->label('Odometer Awal')
                        ->numeric()->minValue(0)->suffix('KM')->required()
                        ->live(onBlur: true)
                        ->helperText('Terisi otomatis dari odometer terakhir armada.'),

                    TextInput::make('odometer_akhir')
                        ->label('Odometer Akhir')
                        ->numeric()->suffix('KM')->required()
                        ->gt('odometer_awal')
                        ->live(onBlur: true),

                    TextInput::make('jumlah_liter')
                        ->label('Jumlah Liter')
                        ->numeric()->inputMode('decimal')->step(0.01)->minValue(0.01)->suffix('L')->required()
                        ->live(onBlur: true),

                    TextInput::make('total_biaya')
                        ->label('Total Biaya')
                        ->numeric()->minValue(0)->prefix('Rp')->required(),

                    Placeholder::make('preview_efisiensi')
                        ->label('Estimasi Efisiensi')
                        ->columnSpanFull()
                        ->content(function (Get $get): string {
                            $liter = (float) $get('jumlah_liter');
                            $jarak = (int) $get('odometer_akhir') - (int) $get('odometer_awal');

                            if ($liter <= 0 || $jarak <= 0) {
                                return '-';
                            }

                            $rasio = round($jarak / $liter, 2);
                            $teks = number_format($rasio, 2, ',', '.') . ' KM/L';

                            return $rasio < LogBbm::AMBANG_BOROS
                                ? "{$teks} — ⚠ di bawah batas 6 KM/L (akan ditandai boros)"
                                : "{$teks} — ✓ normal";
                        }),

                    FileUpload::make('foto_nota')
                        ->label('Foto Nota')
                        ->image()
                        ->disk('local')
                        ->directory('nota-bbm')
                        ->visibility('private')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1280')
                        ->imageResizeTargetHeight('1280')
                        ->maxSize(5120)
                        ->openable()
                        ->required()
                        ->columnSpanFull(),

                    // Driver: otomatis dirinya sendiri. Admin: bisa memilih driver.
                    Hidden::make('driver_id')
                        ->default(fn () => auth()->id())
                        ->visible(fn () => auth()->user()?->isDriver()),
                    Select::make('driver_id')
                        ->label('Driver')
                        ->relationship('driver', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('role', 'driver'))
                        ->searchable()->preload()->required()
                        ->default(fn () => auth()->id())
                        ->visible(fn () => ! auth()->user()?->isDriver()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tgl_pengisian', 'desc')
            ->recordClasses(fn (LogBbm $record) => $record->is_flagged_boros ? 'row-boros' : null)
            ->columns([
                TextColumn::make('tgl_pengisian')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('armada.plat_nomor')->label('Armada')->searchable()->sortable()->weight('bold'),
                TextColumn::make('driver.name')->label('Driver')->searchable()
                    ->visible(fn () => ! auth()->user()?->isDriver()),
                TextColumn::make('odometer_awal')->label('Odo Awal')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                TextColumn::make('odometer_akhir')->label('Odo Akhir')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                TextColumn::make('jumlah_liter')->label('Liter')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.') . ' L'),
                TextColumn::make('total_biaya')->label('Biaya')->sortable()
                    ->formatStateUsing(fn ($state) => Rupiah::format($state))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Rupiah::format($state))),
                TextColumn::make('rasio_efisiensi')->label('Efisiensi (KM/L)')->sortable()->badge()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
                    ->color(fn (LogBbm $record) => $record->is_flagged_boros ? 'danger' : 'success')
                    ->icon(fn (LogBbm $record) => $record->is_flagged_boros ? 'heroicon-m-exclamation-triangle' : null)
                    ->tooltip(fn (LogBbm $record) => $record->is_flagged_boros ? 'Boros: di bawah 6 KM/L' : null),
                ImageColumn::make('foto_nota')->label('Nota')->disk('local')->visibility('private')->square()->toggleable(),
            ])
            ->filters([
                Filter::make('boros')
                    ->label('Hanya yang boros')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_flagged_boros', true)),
                SelectFilter::make('armada_id')->label('Armada')
                    ->relationship('armada', 'plat_nomor')->searchable()->preload(),
                TrashedFilter::make()->label('Data terhapus')->visible(fn () => auth()->user()?->isAdminGarasi()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogBbms::route('/'),
            'create' => Pages\CreateLogBbm::route('/create'),
            'edit' => Pages\EditLogBbm::route('/{record}/edit'),
        ];
    }
}
