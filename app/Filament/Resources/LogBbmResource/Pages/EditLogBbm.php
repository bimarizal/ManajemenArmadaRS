<?php

namespace App\Filament\Resources\LogBbmResource\Pages;

use App\Filament\Resources\LogBbmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLogBbm extends EditRecord
{
    protected static string $resource = LogBbmResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make(), Actions\RestoreAction::make()];
    }
}
