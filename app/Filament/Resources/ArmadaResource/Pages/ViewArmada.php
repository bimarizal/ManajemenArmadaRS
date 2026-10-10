<?php

namespace App\Filament\Resources\ArmadaResource\Pages;

use App\Filament\Resources\ArmadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArmada extends ViewRecord
{
    protected static string $resource = ArmadaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
