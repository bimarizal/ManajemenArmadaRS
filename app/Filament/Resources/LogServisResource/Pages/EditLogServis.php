<?php

namespace App\Filament\Resources\LogServisResource\Pages;

use App\Filament\Resources\LogServisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLogServis extends EditRecord
{
    protected static string $resource = LogServisResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make(), Actions\RestoreAction::make()];
    }
}
