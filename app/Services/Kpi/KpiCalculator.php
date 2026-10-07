<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use App\Services\SettingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Satu-satunya tempat rumus KPI (PRD 7). Rentang [from, to] inklusif per tanggal.
 */
final class KpiCalculator
{
    private const float PRODUCTIVITY_CAP = 1.2;

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly WorkingTimeCalculator $workingTime,
        private readonly ItemPoints $points,
    ) {}

    /**
     * KPI seluruh programmer & QA aktif.
     *
     * @return list<KpiResult>
     */
    public function team(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return User::query()
            ->whereIn('role', [Role::Programmer, Role::Qa])
            ->where('is_active', true)
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): KpiResult => $this->forUser($user, $from, $to))
            ->all();
    }

    public function forUser(User $user, CarbonImmutable $from, CarbonImmutable $to): KpiResult
    {
        return $user->hasRole(Role::Qa)
            ? $this->qa($user, $from, $to)
            : $this->programmer($user, $from, $to);
    }

    /**
     * PRD 7.3. D = item milik programmer yang masuk `done` (approved_at) dalam rentang.
     * Item cancelled otomatis tidak termasuk karena statusnya bukan done.
     */
    public function programmer(User $user, CarbonImmutable $from, CarbonImmutable $to): KpiResult
    {
        [$start, $end] = $this->bounds($from, $to);

        $items = Item::query()
            ->where('assignee_id', $user->id)
            ->where('status', ItemStatus::Done)
            ->whereBetween('approved_at', [$start, $end])
            ->with(['project:id,code', 'statusLogs' => fn ($query) => $query->orderBy('created_at')->orderBy('id')])
            ->orderBy('approved_at')
            ->get();

        $tolerance = $this->settings->float(SettingKey::OnTimeTolerance);
        $hoursPerDay = $this->workingTime->hoursPerDay();
        $weightByPoints = $this->settings->bool(SettingKey::WeightByPoints);

        $rows = $items->map(function (Item $item) use ($tolerance, $hoursPerDay): array {
            $hours = $this->workingTime->hoursInStatus($item->statusLogs, ItemStatus::InProgress, $item->approved_at ?? now());
            $actualDays = $hoursPerDay > 0 ? round($hours / $hoursPerDay, 2) : 0.0;
            $estimate = (float) $item->estimate_days;

            return [
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'points' => $this->points->for($item->difficulty),
                'estimate_days' => $estimate,
                'actual_days' => $actualDays,
                'on_time' => $actualDays <= $estimate * (1 + $tolerance),
                'clean' => $item->qa_fail_count === 0 && $item->reject_count === 0 && $item->reopen_count === 0,
                'qa_fail_count' => $item->qa_fail_count,
                'reject_count' => $item->reject_count,
                'reopen_count' => $item->reopen_count,
                'approved_at' => $item->approved_at?->toIso8601String(),
            ];
        });

        $points = (int) $rows->sum('points');
        $target = $this->settings->float(SettingKey::MonthlyTargetPoints) * $this->monthFraction($from, $to);

        $productivity = $target > 0 ? min($points / $target, self::PRODUCTIVITY_CAP) * 100 : null;
        $timeliness = $this->ratio($rows->all(), 'on_time', $weightByPoints);
        $quality = $this->ratio($rows->all(), 'clean', $weightByPoints);

        $weights = $this->settings->array(SettingKey::ProgrammerWeights);
        $final = ($productivity === null || $timeliness === null || $quality === null)
            ? null
            : (float) $weights['productivity'] * $productivity
                + (float) $weights['timeliness'] * $timeliness
                + (float) $weights['quality'] * $quality;

        return new KpiResult(
            userId: $user->id,
            name: $user->name,
            role: Role::Programmer,
            metrics: [
                'item_count' => $rows->count(),
                'points' => $points,
                'target_points' => round($target, 1),
                'productivity' => $this->round($productivity),
                'timeliness' => $this->round($timeliness),
                'quality' => $this->round($quality),
            ],
            finalScore: $this->round($final),
            items: $rows->values()->all(),
        );
    }

    /**
     * PRD 7.4. Keputusan QA = log ke qa_passed/qa_failed oleh QA tersebut dalam rentang.
     */
    public function qa(User $user, CarbonImmutable $from, CarbonImmutable $to): KpiResult
    {
        [$start, $end] = $this->bounds($from, $to);

        $decisions = ItemStatusLog::query()
            ->where('user_id', $user->id)
            ->whereIn('to_status', [ItemStatus::QaPassed, ItemStatus::QaFailed])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $logsByItem = ItemStatusLog::query()
            ->whereIn('item_id', $decisions->pluck('item_id')->unique())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('item_id');

        $items = Item::query()
            ->with('project:id,code')
            ->whereKey($decisions->pluck('item_id')->unique())
            ->get(['id', 'project_id', 'number', 'title'])
            ->keyBy('id');

        $rows = $decisions->map(function (ItemStatusLog $decision) use ($logsByItem, $items): array {
            /** @var Collection<int, ItemStatusLog> $logs */
            $logs = $logsByItem->get($decision->item_id, collect());
            $item = $items->get($decision->item_id);

            return [
                'id' => $decision->item_id,
                'code' => $item?->code,
                'title' => $item?->title,
                'decision' => $decision->to_status->value,
                'review_hours' => round($this->reviewHours($decision, $logs), 2),
                'overturned' => $decision->to_status === ItemStatus::QaPassed && $this->isOverturned($decision, $logs),
                'decided_at' => $decision->created_at?->toIso8601String(),
            ];
        });

        $count = $rows->count();
        $passes = $rows->where('decision', ItemStatus::QaPassed->value);
        $avgHours = $count > 0 ? (float) $rows->avg('review_hours') : null;
        $targetHours = $this->settings->float(SettingKey::QaReviewTargetHours);
        $throughputTarget = $this->settings->float(SettingKey::QaMonthlyThroughputTarget) * $this->monthFraction($from, $to);

        $throughput = $throughputTarget > 0 ? min($count / $throughputTarget, self::PRODUCTIVITY_CAP) * 100 : null;
        $speed = match (true) {
            $avgHours === null => null,
            $avgHours <= $targetHours => 100.0,
            default => $targetHours / $avgHours * 100,
        };
        $accuracy = $passes->isEmpty() ? null : (1 - $passes->where('overturned', true)->count() / $passes->count()) * 100;

        $weights = $this->settings->array(SettingKey::QaWeights);
        $final = ($count === 0 || $throughput === null || $speed === null)
            ? null
            : (float) $weights['throughput'] * $throughput
                + (float) $weights['speed'] * $speed
                + (float) $weights['accuracy'] * ($accuracy ?? 100.0);

        return new KpiResult(
            userId: $user->id,
            name: $user->name,
            role: Role::Qa,
            metrics: [
                'decision_count' => $count,
                'pass_count' => $passes->count(),
                'fail_count' => $count - $passes->count(),
                'throughput_target' => round($throughputTarget, 1),
                'avg_review_hours' => $this->round($avgHours),
                'throughput' => $this->round($throughput),
                'speed' => $this->round($speed),
                'accuracy' => $this->round($accuracy),
            ],
            finalScore: $this->round($final),
            items: $rows->values()->all(),
        );
    }

    /**
     * Jam kerja dari item masuk ready_for_qa (terakhir sebelum keputusan) hingga keputusan QA.
     *
     * @param  Collection<int, ItemStatusLog>  $logs
     */
    private function reviewHours(ItemStatusLog $decision, Collection $logs): float
    {
        $readyAt = $logs
            ->filter(fn (ItemStatusLog $log): bool => $log->to_status === ItemStatus::ReadyForQa
                && $log->created_at <= $decision->created_at
                && $log->id < $decision->id)
            ->last()
            ?->created_at;

        return $readyAt === null || $decision->created_at === null
            ? 0.0
            : $this->workingTime->workingHoursBetween($readyAt, $decision->created_at);
    }

    /**
     * Lulus QA dianggap keliru bila setelahnya (sebelum keputusan QA berikutnya) item
     * ditolak Manager atau dibuka kembali dari done.
     *
     * @param  Collection<int, ItemStatusLog>  $logs
     */
    private function isOverturned(ItemStatusLog $pass, Collection $logs): bool
    {
        foreach ($logs->filter(fn (ItemStatusLog $log): bool => $log->id > $pass->id) as $log) {
            if (in_array($log->to_status, [ItemStatus::QaPassed, ItemStatus::QaFailed], true)) {
                return false;
            }

            if ($log->to_status === ItemStatus::Rejected
                || ($log->from_status === ItemStatus::Done && $log->to_status === ItemStatus::InProgress)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persentase baris yang memenuhi $flag; opsional dibobot poin item.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function ratio(array $items, string $flag, bool $weightByPoints): ?float
    {
        $rows = collect($items);

        if ($rows->isEmpty()) {
            return null;
        }

        if (! $weightByPoints) {
            return $rows->where($flag, true)->count() / $rows->count() * 100;
        }

        $total = (int) $rows->sum('points');

        return $total === 0 ? null : (int) $rows->where($flag, true)->sum('points') / $total * 100;
    }

    /**
     * Jumlah bulan (pecahan) yang dicakup rentang; satu bulan kalender penuh = 1.0.
     * Dipakai untuk menyesuaikan target bulanan pada rentang tanggal bebas.
     */
    public function monthFraction(CarbonImmutable $from, CarbonImmutable $to): float
    {
        $fraction = 0.0;
        $cursor = $from->startOfDay();
        $last = $to->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $monthEnd = $cursor->endOfMonth()->startOfDay()->min($last);
            $fraction += ($cursor->diffInDays($monthEnd) + 1) / $cursor->daysInMonth;
            $cursor = $monthEnd->addDay();
        }

        return $fraction;
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function bounds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function round(?float $value): ?float
    {
        return $value === null ? null : round($value, 1);
    }
}
