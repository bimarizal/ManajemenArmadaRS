<?php

namespace App\Filament\Resources\LogBbmResource\Pages;

use App\Filament\Resources\LogBbmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLogBbms extends ListRecords
{
    protected static string $resource = LogBbmResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Catat Pengisian BBM')];
    }
}
