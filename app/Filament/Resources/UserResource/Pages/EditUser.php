<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()->isAdminGarasi()) {
            $data['role'] = User::ROLE_DRIVER;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
