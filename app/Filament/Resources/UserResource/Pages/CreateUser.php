<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pengaman sisi server: Penanggung Jawab Garasi hanya boleh membuat akun Driver.
        if (auth()->user()->isPenanggungjawabGarasi()) {
            $data['role'] = User::ROLE_DRIVER;
        }

        return $data;
    }
}
