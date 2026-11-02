<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\QaFinding;
use App\Enums\Role;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use App\Services\SettingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Satu-satunya tempat rumus KPI. Rentang [from, to] inklusif per tanggal.
 */
final class KpiCalculator
{
    /** Kontribusi produktivitas & throughput ke skor akhir maksimal 100%. */
    private const float PRODUCTIVITY_CAP = 1.0;

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
     * KPI Programmer (dokumen "KPI IT Development untuk Programmer"). Poin task = difficulty.
     *
     * - Produktivitas: poin task yang pertama kali diterima (done) dalam rentang ÷
     *   (target poin per hari × hari kerja efektif). Realisasi > 100% ditampilkan,
     *   kontribusi ke skor akhir dibatasi 100%.
     * - Ketepatan waktu: seluruh task dengan deadline (due_date) dalam rentang, termasuk yang belum
     *   selesai; tepat waktu bila versi yang akhirnya lolos QA diserahkan ≤ deadline. Dibobot poin.
     * - Kualitas: task yang testing pertamanya dalam rentang; skor dari kategori bug terberat
     *   pada testing pertama (rubrik di pengaturan). Dibobot poin.
     *
     * Komponen tanpa penyebut bernilai null (N/A). Skor akhir memakai komponen yang tersedia
     * (bobot dinormalisasi) dan berstatus sementara bila ada komponen N/A atau testing pending.
     * Item cancelled tidak dihitung.
     */
    public function programmer(User $user, CarbonImmutable $from, CarbonImmutable $to): KpiResult
    {
        [$start, $end] = $this->bounds($from, $to);
        $now = CarbonImmutable::now();

        $items = Item::query()
            ->where('assignee_id', $user->id)
            ->where('status', '!=', ItemStatus::Cancelled)
            ->where(fn ($query) => $query
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($query) => $query->where('status', ItemStatus::Done)->whereBetween('approved_at', [$start, $end]))
                ->orWhereHas('statusLogs', fn ($query) => $query
                    ->whereIn('to_status', [ItemStatus::Done, ItemStatus::QaPassed, ItemStatus::QaFailed])
                    ->whereBetween('created_at', [$start, $end])))
            ->with(['project:id,code', 'statusLogs' => fn ($query) => $query->orderBy('created_at')->orderBy('id')])
            ->orderBy('id')
            ->get();

        $hoursPerDay = $this->workingTime->hoursPerDay();
        $qualityScores = $this->settings->array(SettingKey::QualityScores);

        $rows = $items->map(function (Item $item) use ($start, $end, $from, $to, $now, $hoursPerDay, $qualityScores): array {
            $logs = $item->statusLogs;
            $acceptedAt = $this->firstAcceptedAt($item, $logs);
            $firstTest = $logs->first(fn (ItemStatusLog $log): bool => in_array($log->to_status, [ItemStatus::QaPassed, ItemStatus::QaFailed], true));
            $firstTestInRange = $firstTest?->created_at !== null && $firstTest->created_at->between($start, $end);
            $finding = $firstTest === null ? null : ($firstTest->qa_finding ?? QaFinding::fallbackFor($firstTest->to_status));
            $submittedAt = $this->acceptedSubmissionAt($item, $logs);
            $dueInRange = $item->due_date !== null
                && $item->due_date->betweenIncluded($from->startOfDay(), $to->endOfDay());

            $hours = $this->workingTime->hoursInStatus($logs, ItemStatus::InProgress, $item->approved_at ?? $now);

            return [
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'type' => $item->type->value,
                'difficulty' => $item->difficulty,
                'points' => $this->points->for($item->difficulty),
                'estimate_days' => (float) $item->estimate_days,
                'actual_days' => $hoursPerDay > 0 ? round($hours / $hoursPerDay, 2) : 0.0,
                'due_date' => $item->due_date?->toDateString(),
                'submitted_at' => $submittedAt?->toIso8601String(),
                'approved_at' => $acceptedAt?->toIso8601String(),
                'accepted' => $acceptedAt !== null && $acceptedAt->between($start, $end),
                'timeliness' => $dueInRange ? $this->timelinessStatus($item, $logs, $submittedAt, $now) : null,
                'first_tested_at' => $firstTestInRange ? $firstTest->created_at->toIso8601String() : null,
                'qa_finding' => $firstTestInRange ? $finding?->value : null,
                'quality_score' => $firstTestInRange && $finding !== null ? (float) ($qualityScores[$finding->value] ?? 0) : null,
                'qa_fail_count' => $item->qa_fail_count,
                'reject_count' => $item->reject_count,
                'reopen_count' => $item->reopen_count,
            ];
        })->filter(fn (array $row): bool => $row['accepted'] || $row['timeliness'] !== null || $row['quality_score'] !== null)
            ->values();

        $accepted = $rows->where('accepted', true);
        $points = (int) $accepted->sum('points');
        $workingDays = $this->workingTime->workingDaysBetween($from, $to);
        $target = $this->settings->float(SettingKey::DailyTargetPoints) * $workingDays;
        $productivityActual = $target > 0 ? $points / $target * 100 : null;
        $productivity = $productivityActual === null ? null : min($productivityActual, self::PRODUCTIVITY_CAP * 100);

        $due = $rows->whereNotNull('timeliness');
        $decided = $due->whereIn('timeliness', ['on_time', 'late']);
        $decidedPoints = (int) $decided->sum('points');
        $onTimePoints = (int) $decided->where('timeliness', 'on_time')->sum('points');
        $pendingPoints = (int) $due->where('timeliness', 'pending')->sum('points');
        $timeliness = $decidedPoints > 0 ? $onTimePoints / $decidedPoints * 100 : null;

        $tested = $rows->whereNotNull('quality_score');
        $testedPoints = (int) $tested->sum('points');
        $quality = $testedPoints > 0
            ? $tested->sum(fn (array $row): float => $row['points'] * $row['quality_score']) / $testedPoints
            : null;

        $components = ['productivity' => $productivity, 'timeliness' => $timeliness, 'quality' => $quality];
        $final = $this->weighted($components, $this->settings->array(SettingKey::ProgrammerWeights));

        return new KpiResult(
            userId: $user->id,
            name: $user->name,
            role: Role::Programmer,
            metrics: [
                'item_count' => $accepted->count(),
                'points' => $points,
                'by_difficulty' => $this->byDifficulty($accepted->all()),
                'working_days' => $workingDays,
                'target_points' => round($target, 1),
                'productivity_actual' => $this->round($productivityActual),
                'productivity' => $this->round($productivity),
                'due_count' => $due->count(),
                'due_points' => (int) $due->sum('points'),
                'on_time_points' => $onTimePoints,
                'pending_points' => $pendingPoints,
                'timeliness' => $this->round($timeliness),
                'tested_count' => $tested->count(),
                'quality' => $this->round($quality),
            ],
            finalScore: $this->round($final),
            items: $rows->all(),
            provisional: in_array(null, $components, true) || $pendingPoints > 0,
        );
    }

    /**
     * Waktu task pertama kali diterima (done). Reopen & approve ulang tidak menambah poin baru.
     *
     * @param  Collection<int, ItemStatusLog>  $logs
     */
    private function firstAcceptedAt(Item $item, Collection $logs): ?CarbonImmutable
    {
        $at = $logs->first(fn (ItemStatusLog $log): bool => $log->to_status === ItemStatus::Done)->created_at
            ?? ($item->status === ItemStatus::Done ? $item->approved_at : null);

        return $at === null ? null : CarbonImmutable::instance($at);
    }

    /**
     * Waktu submit (ready_for_qa) versi yang akhirnya lolos QA: submit terakhir sebelum lulus QA
     * yang tidak kemudian ditolak Manager. Setelah diterima (done) nilainya tetap, sehingga
     * perbaikan pasca-reopen tidak mengubah ketepatan waktu task asal.
     *
     * @param  Collection<int, ItemStatusLog>  $logs
     */
    private function acceptedSubmissionAt(Item $item, Collection $logs): ?CarbonImmutable
    {
        $lastSubmit = null;
        $candidate = null;

        foreach ($logs as $log) {
            match ($log->to_status) {
                ItemStatus::ReadyForQa => $lastSubmit = $log->created_at,
                ItemStatus::QaPassed => $candidate = $lastSubmit,
                ItemStatus::Rejected => $candidate = null,
                default => null,
            };

            if ($log->to_status === ItemStatus::Done && $candidate !== null) {
                return CarbonImmutable::instance($candidate);
            }
        }

        return $item->status === ItemStatus::QaPassed && $candidate !== null
            ? CarbonImmutable::instance($candidate)
            : null;
    }

    /**
     * on_time / late, atau pending bila belum bisa diputuskan: deadline belum lewat, atau versi
     * yang diserahkan sebelum deadline masih menunggu testing. Pending tidak dianggap gagal.
     *
     * @param  Collection<int, ItemStatusLog>  $logs
     */
    private function timelinessStatus(Item $item, Collection $logs, ?CarbonImmutable $submittedAt, CarbonImmutable $now): string
    {
        $deadline = CarbonImmutable::instance($item->due_date)->endOfDay();

        if ($submittedAt !== null) {
            return $submittedAt->lessThanOrEqualTo($deadline) ? 'on_time' : 'late';
        }

        if ($now->lessThanOrEqualTo($deadline)) {
            return 'pending';
        }

        $lastSubmit = $logs->last(fn (ItemStatusLog $log): bool => $log->to_status === ItemStatus::ReadyForQa)?->created_at;
        $awaitingTest = in_array($item->status, [ItemStatus::ReadyForQa, ItemStatus::QaPassed], true);

        return $awaitingTest && $lastSubmit !== null && $lastSubmit->lessThanOrEqualTo($deadline) ? 'pending' : 'late';
    }

    /**
     * Rata-rata berbobot dari komponen yang tersedia; bobot komponen N/A dibagikan ke sisanya.
     *
     * @param  array<string, float|null>  $components
     * @param  array<array-key, mixed>  $weights
     */
    private function weighted(array $components, array $weights): ?float
    {
        $sum = 0.0;
        $weightSum = 0.0;

        foreach ($components as $key => $value) {
            if ($value === null) {
                continue;
            }

            $weight = (float) ($weights[$key] ?? 0);
            $sum += $weight * $value;
            $weightSum += $weight;
        }

        return $weightSum > 0 ? $sum / $weightSum : null;
    }

    /**
     * Jumlah bug & task per difficulty (1-5) dari item selesai.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array{difficulty: int, bug: int, task: int}>
     */
    private function byDifficulty(array $items): array
    {
        $rows = collect($items);

        return array_map(fn (int $level): array => [
            'difficulty' => $level,
            'bug' => $rows->where('difficulty', $level)->where('type', ItemType::Bug->value)->count(),
            'task' => $rows->where('difficulty', $level)->where('type', ItemType::Task->value)->count(),
        ], range(1, 5));
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
