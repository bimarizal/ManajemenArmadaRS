<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogServisResource\Pages;
use App\Models\Armada;
use App\Models\LogServis;
use App\Support\Rupiah;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogServisResource extends Resource
{
    protected static ?string $model = LogServis::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Log Servis';

    protected static ?string $modelLabel = 'Log Servis';

    protected static ?string $pluralModelLabel = 'Log Servis';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isKetuaGarasi()) {
            return null;
        }

        $jumlah = LogServis::where('status_approval', 'Pending')->count();

        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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
            Section::make('Data Servis')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('armada_id')
                        ->label('Armada')
                        ->relationship('armada', 'plat_nomor', modifyQueryUsing: fn (Builder $query) => $query->withoutTrashed())
                        ->getOptionLabelFromRecordUsing(fn (Armada $r) => "{$r->plat_nomor} — {$r->merk_tipe}")
                        ->searchable(['plat_nomor', 'merk_tipe'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, string $operation) {
                            if ($operation === 'create') {
                                $set('odometer_servis', Armada::whereKey($state)->value('odometer_saat_ini'));
                            }
                        }),
                    DatePicker::make('tgl_servis')
                        ->label('Tanggal Servis')
                        ->native(false)->displayFormat('d M Y')
                        ->default(today())->maxDate(today())->required(),
                    TextInput::make('jenis_servis')
                        ->label('Jenis Servis')
                        ->placeholder('Ganti Oli & Cek Rem')
                        ->datalist(['Ganti Oli & Cek Rem', 'Service Berkala', 'Overhaul Rem', 'Ganti Ban', 'Ganti Aki', 'Tune Up', 'Perbaikan AC'])
                        ->required()->maxLength(255),
                    TextInput::make('bengkel')->required()->maxLength(255),
                    TextInput::make('odometer_servis')
                        ->label('Odometer Servis')
                        ->numeric()->minValue(0)->suffix('KM')->required(),
                    TextInput::make('biaya')
                        ->label('Biaya')
                        ->numeric()->minValue(0)->prefix('Rp')->required()
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get): string => (float) $get('biaya') > LogServis::BATAS_APPROVAL
                            ? '⚠ Biaya di atas ' . Rupiah::format(LogServis::BATAS_APPROVAL) . ' — pengajuan akan berstatus Pending dan menunggu persetujuan Ketua Garasi.'
                            : 'Biaya di atas ' . Rupiah::format(LogServis::BATAS_APPROVAL) . ' wajib persetujuan Ketua Garasi.'),
                    Textarea::make('deskripsi')->label('Rincian Servis')->rows(3)->columnSpanFull(),
                    FileUpload::make('foto_kwitansi')
                        ->label('Foto Kwitansi')
                        ->image()->disk('local')->directory('kwitansi-servis')->visibility('private')
                        ->maxSize(5120)->openable()->columnSpanFull(),
                ]),

            Section::make('Status Persetujuan')
                ->hiddenOn('create')
                ->columns(2)
                ->schema([
                    Placeholder::make('info_status')
                        ->label('Status')
                        ->content(fn (?LogServis $record) => $record?->status_approval ?? '-'),
                    Placeholder::make('info_approver')
                        ->label('Diputuskan oleh')
                        ->content(fn (?LogServis $record) => $record?->approver?->name ?? '-'),
                ]),
        ]);
    }

    /** Dipakai oleh Table Action maupun Header Action (halaman View). */
    public static function putuskan(LogServis $record, string $status): void
    {
        abort_unless(auth()->user()?->isKetuaGarasi(), 403);

        $record->update([
            'status_approval' => $status,
            'approved_by' => auth()->id(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tgl_servis', 'desc')
            ->columns([
                TextColumn::make('tgl_servis')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('armada.plat_nomor')->label('Armada')->searchable()->sortable()->weight('bold'),
                TextColumn::make('jenis_servis')->label('Jenis Servis')->searchable(),
                TextColumn::make('bengkel')->searchable()->toggleable(),
                TextColumn::make('odometer_servis')->label('Odometer')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.') . ' KM'),
                TextColumn::make('biaya')->sortable()
                    ->formatStateUsing(fn ($state) => Rupiah::format($state)),
                TextColumn::make('status_approval')->label('Approval')->badge()->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('approver.name')->label('Diputuskan oleh')->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_approval')->label('Status Approval')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
                SelectFilter::make('armada_id')->label('Armada')
                    ->relationship('armada', 'plat_nomor')->searchable()->preload(),
                TrashedFilter::make()->label('Data terhapus')->visible(fn () => auth()->user()?->isAdminGarasi()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),

                // Khusus Ketua Garasi
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pengajuan servis?')
                    ->visible(fn (LogServis $record) => auth()->user()?->isKetuaGarasi() && $record->status_approval === 'Pending')
                    ->action(function (LogServis $record, Tables\Actions\Action $action) {
                        self::putuskan($record, 'Approved');
                        $action->success();
                    })
                    ->successNotificationTitle('Pengajuan servis disetujui'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak pengajuan servis?')
                    ->visible(fn (LogServis $record) => auth()->user()?->isKetuaGarasi() && $record->status_approval === 'Pending')
                    ->action(function (LogServis $record, Tables\Actions\Action $action) {
                        self::putuskan($record, 'Rejected');
                        $action->success();
                    })
                    ->successNotificationTitle('Pengajuan servis ditolak'),
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
            'index' => Pages\ListLogServises::route('/'),
            'create' => Pages\CreateLogServis::route('/create'),
            'view' => Pages\ViewLogServis::route('/{record}'),
            'edit' => Pages\EditLogServis::route('/{record}/edit'),
        ];
    }
}
