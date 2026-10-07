<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Enums\ItemStatus;
use App\Enums\SettingKey;
use App\Models\Holiday;
use App\Models\ItemStatusLog;
use App\Services\SettingRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Menghitung jam kerja efektif: hanya jam kerja (work_start–work_end) pada hari kerja,
 * di luar tanggal di tabel `holidays` (PRD 7.2).
 */
final class WorkingTimeCalculator
{
    /** @var array<string, bool>|null */
    private ?array $holidays = null;

    public function __construct(private readonly SettingRepository $settings) {}

    public function hoursPerDay(?CarbonInterface $date = null): float
    {
        [$startMinutes, $endMinutes] = $this->workWindowMinutes($date);

        return max(0, $endMinutes - $startMinutes) / 60;
    }

    public function workingHoursBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        $start = CarbonImmutable::instance($start)->setTimezone(config('app.timezone'));
        $end = CarbonImmutable::instance($end)->setTimezone(config('app.timezone'));

        if ($end->lessThanOrEqualTo($start)) {
            return 0.0;
        }

        $seconds = 0;

        for ($day = $start->startOfDay(); $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if (! $this->isWorkingDay($day)) {
                continue;
            }

            [$startMinutes, $endMinutes] = $this->workWindowMinutes($day);
            $windowStart = $day->addMinutes($startMinutes)->max($start);
            $windowEnd = $day->addMinutes($endMinutes)->min($end);

            if ($windowEnd->greaterThan($windowStart)) {
                $seconds += $windowEnd->getTimestamp() - $windowStart->getTimestamp();
            }
        }

        return $seconds / 3600;
    }

    public function workingDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $count = 0;
        $end = CarbonImmutable::instance($to)->startOfDay();

        for ($day = CarbonImmutable::instance($from)->startOfDay(); $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if ($this->isWorkingDay($day)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Total jam kerja selama item berada di $status, dijumlahkan dari seluruh siklus pada log.
     *
     * @param  iterable<ItemStatusLog>  $logs  log satu item, urut kronologis
     */
    public function hoursInStatus(iterable $logs, ItemStatus $status, CarbonInterface $until): float
    {
        $hours = 0.0;
        $enteredAt = null;

        foreach ($logs as $log) {
            if ($enteredAt !== null && $log->to_status !== $status) {
                $hours += $this->workingHoursBetween($enteredAt, $log->created_at);
                $enteredAt = null;
            }

            if ($enteredAt === null && $log->to_status === $status) {
                $enteredAt = $log->created_at;
            }
        }

        if ($enteredAt !== null) {
            $hours += $this->workingHoursBetween($enteredAt, $until);
        }

        return $hours;
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        $workDays = array_map('intval', $this->settings->array(SettingKey::WorkDays));

        if (! in_array($date->dayOfWeekIso, $workDays, true)) {
            return false;
        }

        $this->holidays ??= Holiday::query()
            ->pluck('date')
            ->mapWithKeys(fn (CarbonInterface $holiday): array => [$holiday->toDateString() => true])
            ->all();

        return ! isset($this->holidays[$date->toDateString()]);
    }

    /**
     * @return array{int, int} menit sejak tengah malam untuk awal & akhir jam kerja
     */
    private function workWindowMinutes(?CarbonInterface $date = null): array
    {
        if ($date !== null && $date->dayOfWeekIso === 6) {
            return [
                $this->toMinutes($this->settings->string(SettingKey::WorkStartSaturday)),
                $this->toMinutes($this->settings->string(SettingKey::WorkEndSaturday)),
            ];
        }

        return [
            $this->toMinutes($this->settings->string(SettingKey::WorkStart)),
            $this->toMinutes($this->settings->string(SettingKey::WorkEnd)),
        ];
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time) + [0, 0]);

        return $hours * 60 + $minutes;
    }
}
