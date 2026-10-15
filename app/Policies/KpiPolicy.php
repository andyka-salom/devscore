<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Otorisasi fitur KPI & pengaturan (PRD 4). Didaftarkan untuk model KpiPeriod.
 * Chief boleh melihat semuanya, tetapi tidak boleh menutup periode atau mengubah pengaturan.
 */
final class KpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Chief);
    }

    public function viewTeam(User $user): bool
    {
        return $user->hasAnyRole(Role::Manager, Role::Admin, Role::Chief);
    }

    public function viewUser(User $user, User $target): bool
    {
        return $this->viewTeam($user) || $user->id === $target->id;
    }

    public function closePeriod(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::Manager);
    }

    public function viewSettings(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Admin, Role::Chief);
    }

    public function manageSettings(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Admin);
    }
}
