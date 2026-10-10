<?php

namespace App\Policies;

use App\Models\LogBbm;
use App\Models\User;

class LogBbmPolicy
{
    // Semua role boleh membuka menu; Driver dibatasi ke data miliknya di getEloquentQuery().
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LogBbm $log): bool
    {
        return $user->isAdminGarasi() || $user->isKetuaGarasi() || $log->driver_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isDriver() || $user->isAdminGarasi();
    }

    public function update(User $user, LogBbm $log): bool
    {
        return $user->isAdminGarasi();
    }

    public function delete(User $user, LogBbm $log): bool
    {
        return $user->isAdminGarasi();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminGarasi();
    }

    public function restore(User $user, LogBbm $log): bool
    {
        return $user->isAdminGarasi();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdminGarasi();
    }

    // Hapus permanen sengaja tidak diizinkan untuk siapa pun (data biaya harus tetap utuh).
    public function forceDelete(User $user, LogBbm $log): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
