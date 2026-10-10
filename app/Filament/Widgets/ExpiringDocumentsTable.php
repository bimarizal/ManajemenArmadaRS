<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArmadaResource;
use App\Models\Armada;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringDocumentsTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Dokumen Armada Mendekati / Melewati Jatuh Tempo';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isKetuaGarasi() || $user->isAdminGarasi());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Armada::query()->aktif()->dokumenMendekatiJatuhTempo())
            ->defaultSort('tgl_jatuh_tempo_stnk')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Semua dokumen armada aman')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('plat_nomor')->label('Plat Nomor')->searchable()->weight('bold'),
                TextColumn::make('merk_tipe')->label('Merk / Tipe'),
                TextColumn::make('jenis_armada')->label('Jenis')->badge()->color('gray'),
                ArmadaResource::dokumenColumn('tgl_jatuh_tempo_stnk', 'STNK'),
                ArmadaResource::dokumenColumn('tgl_jatuh_tempo_kir', 'KIR'),
                ArmadaResource::dokumenColumn('tgl_jatuh_tempo_pajak', 'Pajak'),
            ])
            ->actions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Armada $record) => ArmadaResource::getUrl(
                        auth()->user()->isAdminGarasi() ? 'edit' : 'view',
                        ['record' => $record],
                    )),
            ]);
    }
}
