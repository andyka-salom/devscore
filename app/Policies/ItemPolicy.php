<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Enums\SettingKey;
use App\Enums\TransitionActor;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Services\SettingRepository;

final class ItemPolicy
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function viewApprovalQueue(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function viewQaQueue(User $user): bool
    {
        return $user->hasAnyRole(Role::Qa, Role::Manager);
    }

    public function viewAvailable(User $user): bool
    {
        return $user->hasRole(Role::Programmer) && $this->settings->bool(SettingKey::SelfAssignEnabled);
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Item $item): bool
    {
        if ($user->hasAnyRole(Role::Manager, Role::Admin)) {
            return true;
        }

        return $item->assignee_id === $user->id
            || $item->qa_id === $user->id
            || $item->created_by === $user->id
            || $user->projects()->whereKey($item->project_id)->exists();
    }

    /**
     * Programmer, QA, dan Manager boleh membuat item di project tempat ia menjadi anggota (PRD 4).
     */
    public function create(User $user, ?Project $project = null): bool
    {
        if (! $user->is_active || ! $user->hasAnyRole(Role::Qa, Role::Manager)) {
            return false;
        }

        return $project === null || $user->hasRole(Role::Manager) || $project->hasMember($user);
    }

    /**
     * Ubah konten item (judul, deskripsi, prioritas, ...) oleh pembuat atau Manager selama belum final.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->is_active
            && ($user->hasRole(Role::Manager) || $item->created_by === $user->id)
            && ! in_array($item->status, [ItemStatus::Done, ItemStatus::Cancelled], true);
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->is_active && $user->hasRole(Role::Manager) && $item->status === ItemStatus::Backlog;
    }

    /**
     * Triage & perubahan estimasi hanya oleh Manager dan QA (PRD 4 & aturan bisnis 2).
     */
    public function updateEstimate(User $user, Item $item): bool
    {
        return $user->is_active
            && $user->hasAnyRole(Role::Manager, Role::Qa)
            && ! in_array($item->status, [ItemStatus::Done, ItemStatus::Cancelled], true);
    }

    /**
     * Penunjukan programmer & QA oleh Manager dan QA selama item belum dikerjakan.
     */
    public function assign(User $user, Item $item): bool
    {
        return $user->is_active
            && $user->hasAnyRole(Role::Manager, Role::Qa)
            && in_array($item->status, [ItemStatus::Backlog, ItemStatus::Assigned], true);
    }

    /**
     * Programmer meng-claim item (self-assign) yang sudah di-triage dan belum punya assignee.
     */
    public function claim(User $user, Item $item): bool
    {
        return $user->is_active
            && $user->hasRole(Role::Programmer)
            && $this->settings->bool(SettingKey::SelfAssignEnabled)
            && $item->status === ItemStatus::Backlog
            && $item->assignee_id === null
            && $item->isTriaged()
            && $user->projects()->whereKey($item->project_id)->exists();
    }

    public function addNote(User $user, Item $item): bool
    {
        return $user->is_active
            && $user->hasAnyRole(Role::Programmer, Role::Qa, Role::Manager)
            && $this->view($user, $item);
    }

    /**
     * Apakah user berwenang menjalankan transisi ke $to (PRD 4 & aturan bisnis 7).
     * Validitas transisi itu sendiri tetap dicek oleh ItemStatus.
     */
    public function transition(User $user, Item $item, ItemStatus $to): bool
    {
        if (! $user->is_active) {
            return false;
        }

        foreach ($item->status->actorsFor($to) as $actor) {
            $allowed = match ($actor) {
                TransitionActor::Manager => $user->hasRole(Role::Manager),
                TransitionActor::Assignee => $user->hasRole(Role::Programmer)
                    && $item->assignee_id === $user->id
                    && ($to !== ItemStatus::Assigned || $this->settings->bool(SettingKey::SelfAssignEnabled)),
                TransitionActor::Qa => $user->hasRole(Role::Qa) && $item->qa_id === $user->id,
            };

            if ($allowed) {
                return true;
            }
        }

        return false;
    }
}
