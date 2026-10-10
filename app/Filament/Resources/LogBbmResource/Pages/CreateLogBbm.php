<?php

namespace App\Filament\Resources\LogBbmResource\Pages;

use App\Filament\Resources\LogBbmResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLogBbm extends CreateRecord
{
    protected static string $resource = LogBbmResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Driver tidak boleh mencatat atas nama driver lain.
        if (auth()->user()->isDriver()) {
            $data['driver_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_flagged_boros) {
            Notification::make()
                ->warning()
                ->title('Pengisian ditandai boros')
                ->body('Efisiensi ' . number_format((float) $this->record->rasio_efisiensi, 2, ',', '.') . ' KM/L (di bawah 6 KM/L).')
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
