<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Programmer meng-claim (self-assign) item backlog yang sudah di-triage.
 * Status tetap diubah lewat TransitionItemStatus.
 */
final class ClaimItem
{
    public function __construct(private readonly TransitionItemStatus $transition) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(Item $item, User $programmer): Item
    {
        return DB::transaction(function () use ($item, $programmer): Item {
            $locked = Item::query()->lockForUpdate()->findOrFail($item->getKey());

            if ($locked->assignee_id !== null) {
                throw ValidationException::withMessages(['item' => 'Item sudah di-claim programmer lain.']);
            }

            Gate::forUser($programmer)->authorize('claim', $locked);

            $locked->assignee_id = $programmer->id;
            $locked->save();

            return $this->transition->handle($locked, ItemStatus::Assigned, $programmer);
        });
    }
}
