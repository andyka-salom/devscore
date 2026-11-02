<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Manajemen user untuk superadmin: Manager dan Administrator.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Admin);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Admin tidak boleh menghapus akunnya sendiri.
     */
    public function delete(User $user, User $target): bool
    {
        return $this->viewAny($user) && $user->id !== $target->id;
    }
}
