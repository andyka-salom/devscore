<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\TestCase;
use App\Models\User;

final class TestCasePolicy
{
    private const array CLOSED_STATUSES = [ItemStatus::Done, ItemStatus::Cancelled];

    /**
     * Daftar test case; isinya dibatasi ke item yang boleh dilihat (TestCaseListQuery).
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Programmer, QA, dan Manager yang bisa melihat item boleh menulis test case selama item belum final.
     */
    public function create(User $user, Item $item): bool
    {
        return $user->is_active
            && $user->hasAnyRole(Role::Programmer, Role::Qa, Role::Manager)
            && ! in_array($item->status, self::CLOSED_STATUSES, true)
            && $user->can('view', $item);
    }

    /**
     * Isi skenario hanya diubah pembuatnya atau Manager.
     */
    public function update(User $user, TestCase $testCase): bool
    {
        return $user->is_active
            && ($user->hasRole(Role::Manager) || $testCase->created_by === $user->id)
            && ! in_array($this->itemOf($testCase)->status, self::CLOSED_STATUSES, true);
    }

    public function delete(User $user, TestCase $testCase): bool
    {
        return $this->update($user, $testCase);
    }

    /**
     * Hasil testing diisi QA atau Manager; pengisinya tercatat sebagai penguji.
     */
    public function recordResult(User $user, TestCase $testCase): bool
    {
        $item = $this->itemOf($testCase);

        return $user->is_active
            && $user->hasAnyRole(Role::Qa, Role::Manager)
            && ! in_array($item->status, self::CLOSED_STATUSES, true)
            && $user->can('view', $item);
    }

    private function itemOf(TestCase $testCase): Item
    {
        return $testCase->loadMissing('item')->item;
    }
}
