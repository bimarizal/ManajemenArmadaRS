<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArmadaResource\Pages;
use App\Filament\Resources\ArmadaResource\RelationManagers;
use App\Models\Armada;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArmadaResource extends Resource
{
    protected static ?string $model = Armada::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Armada';

    protected static ?string $pluralModelLabel = 'Armada';

    protected static ?string $recordTitleAttribute = 'plat_nomor';

    protected static ?int $navigationSort = 1;

    /** Hanya Admin Garasi yang dapat melihat (dan memulihkan) data yang sudah dihapus. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdminGarasi()
            ? $query->withoutGlobalScopes([SoftDeletingScope::class])
            : $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identitas Kendaraan')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('plat_nomor')
                        ->label('Plat Nomor')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
                    TextInput::make('merk_tipe')
                        ->label('Merk / Tipe')
                        ->placeholder('Toyota HiAce / Ambulans')
                        ->required()
                        ->maxLength(255),
                    Select::make('jenis_armada')
                        ->label('Jenis Armada')
                        ->options(Armada::JENIS)
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(Armada::STATUS)
                        ->default('Siap Pakai')
                        ->required(),
                    TextInput::make('odometer_saat_ini')
                        ->label('Odometer Saat Ini')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->suffix('KM')
                        ->required(),
                ]),

            Section::make('Dokumen & Jatuh Tempo')
                ->description('Merah: kedaluwarsa · Kuning: ≤ 14 hari · Hijau: aman')
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    DatePicker::make('tgl_jatuh_tempo_stnk')
                        ->label('Jatuh Tempo STNK')
                        ->native(false)->displayFormat('d M Y')->required(),
                    DatePicker::make('tgl_jatuh_tempo_kir')
                        ->label('Jatuh Tempo KIR')
                        ->native(false)->displayFormat('d M Y')->required(),
                    DatePicker::make('tgl_jatuh_tempo_pajak')
                        ->label('Jatuh Tempo Pajak')
                        ->native(false)->displayFormat('d M Y')->required(),
                ]),
        ]);
    }

    /** Kolom tanggal dokumen dengan badge traffic light (dipakai juga oleh widget). */
    public static function dokumenColumn(string $field, string $label): TextColumn
    {
        return TextColumn::make($field)
            ->label($label)
            ->date('d M Y')
            ->sortable()
            ->badge()
            ->color(fn ($state): string => Armada::statusDokumen($state))
            ->icon(fn ($state): string => match (Armada::statusDokumen($state)) {
                'danger' => 'heroicon-m-x-circle',
                'warning' => 'heroicon-m-exclamation-triangle',
                default => 'heroicon-m-check-circle',
            })
            ->tooltip(fn ($state): string => Armada::labelDokumen($state));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('plat_nomor')
            ->columns([
                TextColumn::make('plat_nomor')->label('Plat Nomor')->searchable()->sortable()->weight('bold'),
                TextColumn::make('merk_tipe')->label('Merk / Tipe')->searchable(),
                TextColumn::make('jenis_armada')->label('Jenis')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Ambulans IGD' => 'danger',
                        'Mobil Jenazah' => 'gray',
                        'Operasional Medis' => 'info',
                        default => 'primary',
                    }),
                TextColumn::make('odometer_saat_ini')->label('Odometer')->sortable()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.') . ' KM'),
                self::dokumenColumn('tgl_jatuh_tempo_stnk', 'STNK'),
                self::dokumenColumn('tgl_jatuh_tempo_kir', 'KIR'),
                self::dokumenColumn('tgl_jatuh_tempo_pajak', 'Pajak'),
                TextColumn::make('status')->label('Status')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Siap Pakai' => 'success',
                        'Dalam Perawatan' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('jenis_armada')->label('Jenis Armada')->options(Armada::JENIS)->multiple(),
                SelectFilter::make('status')->options(Armada::STATUS),
                Filter::make('butuh_perpanjangan')
                    ->label('Dokumen perlu perpanjangan (≤ 14 hari / kedaluwarsa)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->dokumenMendekatiJatuhTempo()),
                TrashedFilter::make()->label('Data terhapus')->visible(fn () => auth()->user()?->isAdminGarasi()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\LogBbmsRelationManager::class,
            RelationManagers\LogServisesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArmadas::route('/'),
            'create' => Pages\CreateArmada::route('/create'),
            'view' => Pages\ViewArmada::route('/{record}'),
            'edit' => Pages\EditArmada::route('/{record}/edit'),
        ];
    }
}
