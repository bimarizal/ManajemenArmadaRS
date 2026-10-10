<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanMasalahResource\Pages;
use App\Models\Armada;
use App\Models\LaporanMasalah;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanMasalahResource extends Resource
{
    protected static ?string $model = LaporanMasalah::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Laporan Kendaraan Bermasalah';

    protected static ?string $modelLabel = 'Laporan Masalah';

    protected static ?string $pluralModelLabel = 'Laporan Kendaraan Bermasalah';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->isDriver()) {
            return null;
        }

        $jumlah = LaporanMasalah::where('status', 'Baru')->count();

        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['armada', 'pelapor']);
        $user = auth()->user();

        return $user?->isDriver() ? $query->where('pelapor_id', $user->id) : $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('armada_id')
                ->label('Armada')
                ->relationship('armada', 'plat_nomor', modifyQueryUsing: fn (Builder $query) => $query->withoutTrashed())
                ->getOptionLabelFromRecordUsing(fn (Armada $r) => "{$r->plat_nomor} — {$r->merk_tipe}")
                ->searchable(['plat_nomor', 'merk_tipe'])
                ->preload()
                ->required(),
            TextInput::make('judul')
                ->label('Masalah')
                ->placeholder('Contoh: Rem berdecit, AC tidak dingin')
                ->datalist(['Rem bermasalah', 'Mesin berasap / overheat', 'AC tidak dingin', 'Ban bocor / aus', 'Lampu / sirine mati', 'Aki lemah'])
                ->required()->maxLength(255),
            Textarea::make('deskripsi')->label('Keterangan')->rows(4)->required(),
            FileUpload::make('foto')
                ->label('Foto (opsional)')
                ->image()->disk('local')->directory('laporan-masalah')->visibility('private')
                ->maxSize(5120)->openable(),
            Select::make('status')
                ->options(['Baru' => 'Baru', 'Diproses' => 'Diproses', 'Selesai' => 'Selesai'])
                ->default('Baru')
                ->required()
                ->visible(fn () => ! auth()->user()?->isDriver()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Dilaporkan')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('armada.plat_nomor')->label('Armada')->searchable()->weight('bold'),
                TextColumn::make('pelapor.name')->label('Pelapor')
                    ->visible(fn () => ! auth()->user()?->isDriver()),
                TextColumn::make('judul')->label('Masalah')->searchable()->wrap(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Selesai' => 'success',
                        'Diproses' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanMasalahs::route('/'),
        ];
    }
}
