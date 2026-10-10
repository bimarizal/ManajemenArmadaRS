<?php

namespace App\Filament\Resources\LaporanMasalahResource\Pages;

use App\Filament\Resources\LaporanMasalahResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanMasalahs extends ManageRecords
{
    protected static string $resource = LaporanMasalahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Laporkan Kendaraan Bermasalah')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['pelapor_id'] = auth()->id();

                    // Driver tidak boleh menentukan status laporan.
                    if (auth()->user()->isDriver()) {
                        $data['status'] = 'Baru';
                    }

                    return $data;
                }),
        ];
    }
}
