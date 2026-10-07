<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar item dengan filter (PRD 5.3) yang dibatasi ke item yang boleh dilihat user.
 */
final class ItemListQuery
{
    /** Kunci sort yang diizinkan => kolom SQL. */
    public const array SORTS = [
        'updated_at' => 'items.updated_at',
        'code' => 'items.number',
        'title' => 'items.title',
        'priority' => 'items.priority',
        'estimate' => 'items.estimate_days',
        'status' => 'items.status',
    ];

    public function __construct(private readonly User $viewer) {}

    /**
     * @param  array{search?: string|null, project?: int|null, type?: string|null, status?: string|null, priority?: string|null, assignee?: int|null, mine?: bool, sort?: string, direction?: string}  $filters
     * @return LengthAwarePaginator<int, Item>
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $column = self::SORTS[$filters['sort'] ?? ''] ?? self::SORTS['updated_at'];
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->visible()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when($filters['project'] ?? null, fn (Builder $query, int $id) => $query->where('items.project_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('items.type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('items.status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('items.priority', $priority))
            ->when($filters['assignee'] ?? null, fn (Builder $query, int $id) => $query->where('items.assignee_id', $id))
            ->when($filters['mine'] ?? false, fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('items.assignee_id', $this->viewer->id)
                ->orWhere('items.qa_id', $this->viewer->id)
                ->orWhere('items.created_by', $this->viewer->id)))
            ->select('items.*')
            ->with(['project:id,code,name', 'assignee:id,name', 'qa:id,name'])
            ->orderBy($column, $direction)
            ->orderByDesc('items.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Item backlog yang sudah di-triage, punya QA, belum ada programmer, di project tempat viewer anggota.
     *
     * @return LengthAwarePaginator<int, Item>
     */
    public function available(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->availableQuery()
            ->when($search, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->with(['project:id,code,name', 'assignee:id,name', 'qa:id,name'])
            ->orderByRaw("case items.priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->oldest('items.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Builder<Item>
     */
    public function availableQuery(): Builder
    {
        return Item::query()
            ->where('items.status', ItemStatus::Backlog)
            ->whereNull('items.assignee_id')
            ->whereNotNull('items.difficulty')
            ->whereNotNull('items.estimate_days')
            ->whereIn('items.project_id', $this->viewer->projects()->select('projects.id'));
    }

    /**
     * @return Builder<Item>
     */
    private function visible(): Builder
    {
        $query = Item::query();

        if ($this->viewer->hasAnyRole(Role::Manager, Role::Admin)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereIn('items.project_id', $this->viewer->projects()->select('projects.id'))
            ->orWhere('items.assignee_id', $this->viewer->id)
            ->orWhere('items.qa_id', $this->viewer->id)
            ->orWhere('items.created_by', $this->viewer->id));
    }

    /**
     * Cari berdasarkan judul atau kode (mis. "ERP-42" atau "42").
     *
     * @param  Builder<Item>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search): void {
            $q->where('items.title', 'ilike', '%'.addcslashes($search, '%_\\').'%');

            if (preg_match('/^(?:([A-Za-z][A-Za-z0-9]*)-)?(\d+)$/', $search, $match) === 1) {
                $q->orWhere(fn (Builder $code) => $code
                    ->where('items.number', (int) $match[2])
                    ->when($match[1] !== '', fn (Builder $p) => $p->whereHas(
                        'project',
                        fn (Builder $project) => $project->where('code', strtoupper($match[1])),
                    )));
            }
        });
    }
}
