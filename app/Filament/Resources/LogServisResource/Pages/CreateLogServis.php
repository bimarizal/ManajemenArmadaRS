<?php

namespace App\Filament\Resources\LogServisResource\Pages;

use App\Filament\Resources\LogServisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLogServis extends CreateRecord
{
    protected static string $resource = LogServisResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
