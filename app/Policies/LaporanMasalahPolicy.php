<?php

namespace App\Policies;

use App\Models\LaporanMasalah;
use App\Models\User;

class LaporanMasalahPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LaporanMasalah $laporan): bool
    {
        return $user->isAdminGarasi() || $user->isKetuaGarasi() || $laporan->pelapor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isDriver() || $user->isAdminGarasi();
    }

    public function update(User $user, LaporanMasalah $laporan): bool
    {
        return $user->isAdminGarasi();
    }

    public function delete(User $user, LaporanMasalah $laporan): bool
    {
        return $user->isAdminGarasi();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminGarasi();
    }
}
