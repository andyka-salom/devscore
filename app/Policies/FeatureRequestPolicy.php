<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\FeatureRequest;
use App\Models\Project;
use App\Models\User;

final class FeatureRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(Role::Programmer, Role::Qa, Role::Manager, Role::Chief);
    }

    /**
     * Programmer, QA, dan Manager boleh mengajukan untuk project yang diikuti (Manager: semua project).
     */
    public function create(User $user, ?Project $project = null): bool
    {
        if (! $user->is_active || ! $user->hasAnyRole(Role::Programmer, Role::Qa, Role::Manager)) {
            return false;
        }

        return $project === null || $user->hasRole(Role::Manager) || $project->hasMember($user);
    }

    public function viewReviewQueue(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::Manager);
    }

    public function review(User $user, FeatureRequest $request): bool
    {
        return $this->viewReviewQueue($user);
    }
}
