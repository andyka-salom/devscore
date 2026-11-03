<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\TestCaseResult;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar test case lintas item, dibatasi ke item yang boleh dilihat viewer.
 */
final class TestCaseListQuery
{
    public function __construct(private readonly User $viewer) {}

    /**
     * @param  array{search?: string|null, project?: int|null, result?: string|null}  $filters
     * @return LengthAwarePaginator<int, TestCase>
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->base($filters)
            ->when($filters['result'] ?? null, fn (Builder $query, string $result) => $query->where('test_cases.result', $result))
            ->with(['item:id,project_id,number,title,status', 'item.project:id,code,name', 'creator:id,name', 'tester:id,name'])
            ->latest('test_cases.updated_at')
            ->orderByDesc('test_cases.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Jumlah per hasil untuk filter yang sama (tanpa filter hasil), termasuk kunci `all`.
     *
     * @param  array{search?: string|null, project?: int|null}  $filters
     * @return array<string, int>
     */
    public function counts(array $filters): array
    {
        $counts = $this->base($filters)
            ->selectRaw('result, count(*) as aggregate')
            ->groupBy('result')
            ->pluck('aggregate', 'result')
            ->map(fn (mixed $count): int => (int) $count);

        $result = ['all' => $counts->sum()];

        foreach (TestCaseResult::cases() as $case) {
            $result[$case->value] = $counts->get($case->value, 0);
        }

        return $result;
    }

    /**
     * @param  array{search?: string|null, project?: int|null}  $filters
     * @return Builder<TestCase>
     */
    private function base(array $filters): Builder
    {
        return TestCase::query()
            ->whereIn('test_cases.item_id', (new ItemListQuery($this->viewer))->visible()->select('items.id'))
            ->when($filters['project'] ?? null, fn (Builder $query, int $id) => $query
                ->whereHas('item', fn (Builder $item) => $item->where('project_id', $id)))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where('test_cases.scenario', 'ilike', '%'.addcslashes($search, '%_\\').'%'));
    }
}
