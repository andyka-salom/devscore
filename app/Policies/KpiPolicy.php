<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Otorisasi fitur KPI & pengaturan (PRD 4). Didaftarkan untuk model KpiPeriod.
 */
final class KpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::Manager);
    }

    public function viewTeam(User $user): bool
    {
        return $user->hasAnyRole(Role::Manager, Role::Admin);
    }

    public function viewUser(User $user, User $target): bool
    {
        return $this->viewTeam($user) || $user->id === $target->id;
    }

    public function closePeriod(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::Manager);
    }

    public function manageSettings(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Admin);
    }
}
