<?php

namespace App\Policies;

use App\Models\Armada;
use App\Models\User;

class ArmadaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPenanggungjawabGarasi() || $user->isKasubagRumahtangga();
    }

    public function view(User $user, Armada $armada): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isPenanggungjawabGarasi();
    }

    public function update(User $user, Armada $armada): bool
    {
        return $user->isPenanggungjawabGarasi();
    }

    public function delete(User $user, Armada $armada): bool
    {
        return $user->isPenanggungjawabGarasi();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isPenanggungjawabGarasi();
    }

    public function restore(User $user, Armada $armada): bool
    {
        return $user->isPenanggungjawabGarasi();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isPenanggungjawabGarasi();
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
