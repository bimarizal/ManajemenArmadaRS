<?php

namespace App\Policies;

use App\Models\Armada;
use App\Models\User;

class ArmadaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminGarasi() || $user->isKetuaGarasi();
    }

    public function view(User $user, Armada $armada): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminGarasi();
    }

    public function update(User $user, Armada $armada): bool
    {
        return $user->isAdminGarasi();
    }

    public function delete(User $user, Armada $armada): bool
    {
        return $user->isAdminGarasi();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminGarasi();
    }

    public function restore(User $user, Armada $armada): bool
    {
        return $user->isAdminGarasi();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdminGarasi();
    }

    // Hapus permanen sengaja tidak diizinkan untuk siapa pun (data biaya harus tetap utuh).
    public function forceDelete(User $user, Armada $armada): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
