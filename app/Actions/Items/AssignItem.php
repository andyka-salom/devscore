<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Manager menunjuk programmer dan QA item (selama backlog/assigned).
 * Assignee boleh dikosongkan agar item bisa di-claim programmer.
 */
final class AssignItem
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(Item $item, User $actor, ?int $assigneeId, int $qaId): Item
    {
        Gate::forUser($actor)->authorize('assign', $item);

        $this->ensureRole($assigneeId, Role::Programmer, 'assignee_id', 'Assignee harus programmer aktif.');
        $this->ensureRole($qaId, Role::Qa, 'qa_id', 'QA harus user QA aktif.');

        return DB::transaction(function () use ($item, $actor, $assigneeId, $qaId): Item {
            $locked = Item::query()->lockForUpdate()->findOrFail($item->getKey());

            if ($assigneeId === null && $locked->status !== ItemStatus::Backlog) {
                throw ValidationException::withMessages([
                    'assignee_id' => 'Item yang sudah ditugaskan wajib memiliki programmer.',
                ]);
            }

            if ($locked->assignee_id === $assigneeId && $locked->qa_id === $qaId) {
                return $locked;
            }

            $before = $this->describe($locked->assignee_id, $locked->qa_id);
            $locked->assignee_id = $assigneeId;
            $locked->qa_id = $qaId;
            $locked->save();

            $locked->notes()->create([
                'user_id' => $actor->id,
                'body' => "Penugasan diubah ({$before} → {$this->describe($assigneeId, $qaId)})",
                'is_system' => true,
            ]);

            return $locked;
        });
    }

    private function ensureRole(?int $userId, Role $role, string $field, string $message): void
    {
        if ($userId === null) {
            return;
        }

        $valid = User::query()->whereKey($userId)->where('role', $role)->where('is_active', true)->exists();

        if (! $valid) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function describe(?int $assigneeId, ?int $qaId): string
    {
        $names = User::query()->whereKey(array_filter([$assigneeId, $qaId]))->pluck('name', 'id');

        return sprintf(
            'programmer: %s, QA: %s',
            $assigneeId === null ? '-' : $names->get($assigneeId, '-'),
            $qaId === null ? '-' : $names->get($qaId, '-'),
        );
    }
}
