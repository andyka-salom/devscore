<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\Role;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Antrian item pada satu status (mis. antrian QA, antrian approval) dengan
 * filter tipe, sorting ter-whitelist, dan waktu masuk antrian (`queued_at`).
 */
final class ItemQueueQuery
{
    /** Kunci sort yang diizinkan => kolom SQL. */
    public const array SORTS = [
        'queued_at' => 'queued_at',
        'code' => 'items.number',
        'title' => 'items.title',
        'estimate' => 'items.estimate_days',
    ];

    public const string DEFAULT_SORT = 'queued_at';

    private function __construct(
        private readonly ItemStatus $status,
        private readonly ?int $qaId = null,
        private ?int $projectId = null,
    ) {}

    public function filterByProject(?int $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public static function approval(): self
    {
        return new self(ItemStatus::QaPassed);
    }

    /**
     * QA hanya melihat item di mana ia ditunjuk; Manager melihat semua.
     */
    public static function qa(User $user): self
    {
        return new self(ItemStatus::ReadyForQa, $user->hasRole(Role::Qa) ? $user->id : null);
    }

    public function count(): int
    {
        return $this->base()->count();
    }

    /**
     * @return array<string, int> jumlah per tipe item, termasuk kunci `all`
     */
    public function countsByType(): array
    {
        $counts = $this->base()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn (mixed $count): int => (int) $count);

        $result = ['all' => $counts->sum()];

        foreach (ItemType::cases() as $type) {
            $result[$type->value] = $counts->get($type->value, 0);
        }

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, Item>
     */
    public function paginate(?ItemType $type, string $sort, string $direction, int $perPage = 15): LengthAwarePaginator
    {
        $column = self::SORTS[$sort] ?? self::SORTS[self::DEFAULT_SORT];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return $this->base()
            ->select('items.*')
            ->addSelect(['queued_at' => ItemStatusLog::query()
                ->select('created_at')
                ->whereColumn('item_status_logs.item_id', 'items.id')
                ->where('to_status', $this->status)
                ->latest('created_at')
                ->limit(1),
            ])
            ->when($type !== null, fn (Builder $query) => $query->where('items.type', $type))
            ->with(['project:id,code,name', 'assignee:id,name', 'qa:id,name'])
            ->orderBy($column, $direction)
            ->orderBy('items.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Builder<Item>
     */
    private function base(): Builder
    {
        return Item::query()
            ->where('items.status', $this->status)
            ->when($this->qaId !== null, fn (Builder $query) => $query->where('items.qa_id', $this->qaId))
            ->when($this->projectId !== null, fn (Builder $query) => $query->where('items.project_id', $this->projectId));
    }
}
