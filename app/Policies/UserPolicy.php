<?php

namespace App\Policies;

use App\Models\User;

/**
 * Ketua Garasi : mengelola semua akun.
 * Admin Garasi : hanya mengelola akun Driver.
 * Driver       : tidak punya akses.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdminGarasi() || $actor->isKetuaGarasi();
    }

    public function view(User $actor, User $target): bool
    {
        return $this->kelola($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->kelola($actor, $target);
    }

    // Tidak boleh menghapus diri sendiri atau akun yang sudah punya riwayat BBM (gunakan "Nonaktifkan").
    public function delete(User $actor, User $target): bool
    {
        return $this->kelola($actor, $target)
            && $actor->isNot($target)
            && ! $target->logBbms()->withTrashed()->exists(); // log yang di-soft-delete tetap menahan akun (FK)
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    private function kelola(User $actor, User $target): bool
    {
        if ($actor->isKetuaGarasi()) {
            return true;
        }

        return $actor->isAdminGarasi() && $target->isDriver();
    }
}
