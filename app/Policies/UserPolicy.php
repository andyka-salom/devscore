<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Manajemen user hanya untuk Administrator (superadmin).
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::Admin);
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
