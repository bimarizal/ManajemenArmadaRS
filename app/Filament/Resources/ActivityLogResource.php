<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Armada;
use App\Models\LaporanMasalah;
use App\Models\LogBbm;
use App\Models\LogServis;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/** Jejak audit (hanya baca). Hanya Ketua Garasi yang dapat membuka. */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Jejak Audit';

    protected static ?string $modelLabel = 'Aktivitas';

    protected static ?string $pluralModelLabel = 'Jejak Audit';

    protected static ?int $navigationSort = 1;

    public const LABEL_OBJEK = [
        Armada::class => 'Armada',
        LogBbm::class => 'Log BBM',
        LogServis::class => 'Log Servis',
        LaporanMasalah::class => 'Laporan Masalah',
        User::class => 'Pengguna',
    ];

    public const LABEL_EVENT = [
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
        'login' => 'Login',
        'logout' => 'Logout',
        'login_gagal' => 'Login gagal',
    ];

    public static function canViewAny(): bool
    {
        return auth()->user()?->isKetuaGarasi() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('causer');
    }

    protected static function teks(mixed $nilai): string
    {
        if (is_null($nilai)) {
            return '-';
        }

        return is_scalar($nilai) ? (string) $nilai : json_encode($nilai, JSON_UNESCAPED_UNICODE);
    }

    protected static function renderPerubahan(Activity $aktivitas): string
    {
        $baru = (array) $aktivitas->properties->get('attributes', []);
        $lama = (array) $aktivitas->properties->get('old', []);
        $kolom = array_unique(array_merge(array_keys($lama), array_keys($baru)));

        if (! $kolom) {
            return '<em>Tidak ada rincian atribut.</em>';
        }

        $baris = '';
        foreach ($kolom as $k) {
            $baris .= '<tr><td><strong>' . e($k) . '</strong></td><td>' . e(self::teks($lama[$k] ?? null))
                . '</td><td>' . e(self::teks($baru[$k] ?? null)) . '</td></tr>';
        }

        return '<table><thead><tr><th>Kolom</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>' . $baris . '</tbody></table>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('causer.name')->label('Pengguna')->placeholder('Sistem / tidak dikenal'),
                TextColumn::make('event')->label('Aksi')->badge()
                    ->formatStateUsing(fn (?string $state) => self::LABEL_EVENT[$state] ?? ($state ?? '-'))
                    ->color(fn (?string $state) => match ($state) {
                        'created', 'restored', 'login' => 'success',
                        'updated' => 'warning',
                        'deleted', 'login_gagal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')->label('Objek')
                    ->formatStateUsing(fn (?string $state, $record) => $state
                        ? (self::LABEL_OBJEK[$state] ?? class_basename($state)) . ($record->subject_id ? " #{$record->subject_id}" : '')
                        : '-'),
                TextColumn::make('description')->label('Keterangan')->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label('Aksi')->options(self::LABEL_EVENT),
                SelectFilter::make('subject_type')->label('Objek')->options(self::LABEL_OBJEK),
                SelectFilter::make('causer_id')->label('Pengguna')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Filter::make('rentang_waktu')
                    ->form([
                        DatePicker::make('dari')->label('Dari tanggal')->native(false),
                        DatePicker::make('sampai')->label('Sampai tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Aktivitas')
                    ->modalWidth('3xl')
                    ->infolist([
                        TextEntry::make('description')->label('Keterangan'),
                        TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s'),
                        TextEntry::make('ip')->label('Alamat IP')
                            ->state(fn (Activity $record) => $record->properties->get('ip', '-')),
                        TextEntry::make('perubahan')->label('Perubahan Data')->columnSpanFull()->html()
                            ->state(fn (Activity $record) => self::renderPerubahan($record)),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
