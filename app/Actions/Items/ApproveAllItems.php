<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\User;
use App\Queries\ItemQueueQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manager menyetujui (qa_passed → done) seluruh item di antrian approval sesuai filter.
 * Setiap item tetap melewati TransitionItemStatus; satu kegagalan membatalkan semuanya.
 */
final class ApproveAllItems
{
    public function __construct(private readonly TransitionItemStatus $transition) {}

    /**
     * @return int jumlah item yang disetujui
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(User $manager, ?int $projectId = null, ?ItemType $type = null): int
    {
        return DB::transaction(function () use ($manager, $projectId, $type): int {
            $items = ItemQueueQuery::approval()->filterByProject($projectId)->items($type);

            $items->each(fn (Item $item): Item => $this->transition->handle($item, ItemStatus::Done, $manager));

            return $items->count();
        });
    }
}
