<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\KpiPeriod;
use App\Models\KpiSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Sisi baca KPI. Bila rentang tepat satu bulan yang sudah ditutup, data diambil dari
 * kpi_snapshots (tidak dihitung ulang, CLAUDE.md Aturan Domain 6); selain itu dihitung langsung.
 */
final class KpiReport
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    /**
     * Periode tertutup yang tepat sama dengan rentang, bila ada.
     */
    public function closedPeriodFor(CarbonImmutable $from, CarbonImmutable $to): ?KpiPeriod
    {
        $period = $this->periodMatching($from, $to);

        return $period?->isClosed() ? $period : null;
    }

    public function periodMatching(CarbonImmutable $from, CarbonImmutable $to): ?KpiPeriod
    {
        $isFullMonth = $from->isSameDay($from->startOfMonth())
            && $to->isSameDay($from->endOfMonth());

        if (! $isFullMonth) {
            return null;
        }

        return KpiPeriod::query()->where('year', $from->year)->where('month', $from->month)->first();
    }

    /**
     * @param  User|null  $only  batasi ke satu user (untuk user non-manager)
     * @return list<array<string, mixed>>
     */
    public function rows(CarbonImmutable $from, CarbonImmutable $to, ?User $only = null): array
    {
        $closed = $this->closedPeriodFor($from, $to);

        if ($closed !== null) {
            return $closed->snapshots()
                ->when($only !== null, fn ($query) => $query->where('user_id', $only?->id))
                ->orderBy('role')
                ->get()
                ->map(fn (KpiSnapshot $snapshot): array => $snapshot->metrics)
                ->values()
                ->all();
        }

        $results = $only === null
            ? $this->calculator->team($from, $to)
            : [$this->calculator->forUser($only, $from, $to)];

        return array_map(fn (KpiResult $result): array => $result->toArray(), $results);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function rowFor(User $user, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        return $this->rows($from, $to, $user)[0] ?? null;
    }
}
