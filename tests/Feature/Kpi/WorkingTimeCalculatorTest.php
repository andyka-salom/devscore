<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Models\Holiday;
use App\Models\ItemStatusLog;
use App\Services\Kpi\WorkingTimeCalculator;
use Carbon\CarbonImmutable;

// 2026-10-05 = Senin. Jam kerja default 08:00–17:00, Senin–Jumat.

function at(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'Asia/Jakarta');
}

function calculator(): WorkingTimeCalculator
{
    return app(WorkingTimeCalculator::class);
}

it('menghitung jam kerja dalam satu hari', function (): void {
    expect(calculator()->workingHoursBetween(at('2026-10-05 09:00'), at('2026-10-05 12:00')))->toBe(3.0);
});

it('memotong di luar jam kerja', function (): void {
    expect(calculator()->workingHoursBetween(at('2026-10-05 06:00'), at('2026-10-05 20:00')))->toBe(9.0)
        ->and(calculator()->workingHoursBetween(at('2026-10-05 18:00'), at('2026-10-06 07:00')))->toBe(0.0);
});

it('melewati akhir pekan', function (): void {
    // Jumat 16:00 → Senin 09:00 = 1 jam + 1 jam
    expect(calculator()->workingHoursBetween(at('2026-10-09 16:00'), at('2026-10-12 09:00')))->toBe(2.0);
});

it('mengabaikan hari libur', function (): void {
    Holiday::query()->create(['date' => '2026-10-06', 'name' => 'Libur']);

    expect(calculator()->workingHoursBetween(at('2026-10-05 08:00'), at('2026-10-07 17:00')))->toBe(18.0)
        ->and(calculator()->workingDaysBetween(at('2026-10-05'), at('2026-10-09')))->toBe(4);
});

it('menjumlahkan seluruh siklus in_progress dan mengabaikan on_hold', function (): void {
    $logs = collect([
        ['to' => ItemStatus::InProgress, 'at' => '2026-10-05 08:00'],
        ['to' => ItemStatus::OnHold, 'at' => '2026-10-05 10:00'],
        ['to' => ItemStatus::InProgress, 'at' => '2026-10-05 15:00'],
        ['to' => ItemStatus::ReadyForQa, 'at' => '2026-10-05 17:00'],
        ['to' => ItemStatus::QaFailed, 'at' => '2026-10-06 10:00'],
        ['to' => ItemStatus::InProgress, 'at' => '2026-10-06 11:00'],
        ['to' => ItemStatus::ReadyForQa, 'at' => '2026-10-06 12:00'],
    ])->map(function (array $row): ItemStatusLog {
        $log = new ItemStatusLog(['to_status' => $row['to']]);
        $log->created_at = at($row['at']);

        return $log;
    });

    expect(calculator()->hoursInStatus($logs, ItemStatus::InProgress, at('2026-10-07 17:00')))->toBe(5.0);
});
