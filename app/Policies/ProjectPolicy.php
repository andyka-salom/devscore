<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole(Role::Manager, Role::Admin) || $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Manager, Role::Qa);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->create($user);
    }

    public function addNote(User $user, Project $project): bool
    {
        return $user->is_active
            && $user->hasAnyRole(Role::Programmer, Role::Qa, Role::Manager)
            && $this->view($user, $project);
    }
}
