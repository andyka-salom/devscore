<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use App\Services\Kpi\KpiCalculator;
use Carbon\CarbonImmutable;

function kpi(): KpiCalculator
{
    return app(KpiCalculator::class);
}

function october(): array
{
    return [CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31')];
}

function writeLog(Item $item, ?ItemStatus $from, ItemStatus $to, string $at, User $user): void
{
    $log = new ItemStatusLog(['item_id' => $item->id, 'from_status' => $from, 'to_status' => $to, 'user_id' => $user->id]);
    $log->created_at = CarbonImmutable::parse($at, 'Asia/Jakarta');
    $log->save();
}

/**
 * Item done milik $programmer dengan satu siklus in_progress [$start, $end].
 *
 * @param  array<string, mixed>  $attributes
 */
function doneItem(User $programmer, string $start, string $end, array $attributes = []): Item
{
    $item = Item::factory()->create([
        'status' => ItemStatus::Done,
        'assignee_id' => $programmer->id,
        'difficulty' => 3,
        'estimate_days' => '1.0',
        'started_at' => $start,
        'approved_at' => '2026-10-20 10:00',
        ...$attributes,
    ]);

    writeLog($item, ItemStatus::Assigned, ItemStatus::InProgress, $start, $programmer);
    writeLog($item, ItemStatus::InProgress, ItemStatus::ReadyForQa, $end, $programmer);

    return $item;
}

it('menghitung KPI programmer sesuai rumus PRD 7.3', function (): void {
    $programmer = User::factory()->programmer()->create();
    // 5 poin, 1 hari kerja aktual (9 jam) vs estimasi 1 → tepat waktu, bersih
    doneItem($programmer, '2026-10-05 08:00', '2026-10-05 17:00', ['difficulty' => 4]);
    // 3 poin, 2 hari aktual vs estimasi 1 (+10%) → terlambat; gagal QA sekali → tidak bersih
    doneItem($programmer, '2026-10-05 08:00', '2026-10-06 17:00', ['qa_fail_count' => 1, 'approved_at' => '2026-10-21 10:00']);

    $result = kpi()->programmer($programmer, ...october());

    expect($result->metrics)->toMatchArray([
        'item_count' => 2,
        'points' => 8,
        'target_points' => 20.0,
        'productivity' => 40.0,
        'timeliness' => 50.0,
        'quality' => 50.0,
    ])->and($result->finalScore)->toBe(46.0);

    expect(collect($result->items)->pluck('actual_days')->all())->toBe([1.0, 2.0]);
});

it('membatasi produktivitas pada 120%', function (): void {
    setSetting(SettingKey::MonthlyTargetPoints, 4);
    $programmer = User::factory()->programmer()->create();
    doneItem($programmer, '2026-10-05 08:00', '2026-10-05 10:00', ['difficulty' => 5]);

    expect(kpi()->programmer($programmer, ...october())->metrics['productivity'])->toBe(120.0);
});

it('menampilkan T, Q, dan skor akhir null bila tidak ada item selesai', function (): void {
    $result = kpi()->programmer(User::factory()->programmer()->create(), ...october());

    expect($result->metrics)->toMatchArray(['item_count' => 0, 'productivity' => 0.0, 'timeliness' => null, 'quality' => null])
        ->and($result->finalScore)->toBeNull();
});

it('tidak menghitung item cancelled atau item yang selesai di luar rentang', function (): void {
    $programmer = User::factory()->programmer()->create();
    Item::factory()->cancelled()->create(['assignee_id' => $programmer->id, 'difficulty' => 5, 'approved_at' => '2026-10-10']);
    doneItem($programmer, '2026-09-01 08:00', '2026-09-01 12:00', ['approved_at' => '2026-09-30 10:00']);

    expect(kpi()->programmer($programmer, ...october())->metrics['item_count'])->toBe(0);
});

it('menganggap item yang pernah di-reopen tidak bersih', function (): void {
    $programmer = User::factory()->programmer()->create();
    doneItem($programmer, '2026-10-05 08:00', '2026-10-05 12:00', ['reopen_count' => 1]);

    expect(kpi()->programmer($programmer, ...october())->metrics['quality'])->toBe(0.0);
});

it('tidak menghitung waktu on_hold sebagai durasi kerja', function (): void {
    $programmer = User::factory()->programmer()->create();
    $item = Item::factory()->create([
        'status' => ItemStatus::Done,
        'assignee_id' => $programmer->id,
        'difficulty' => 2,
        'estimate_days' => '0.5',
        'approved_at' => '2026-10-20 10:00',
    ]);
    writeLog($item, ItemStatus::Assigned, ItemStatus::InProgress, '2026-10-05 08:00', $programmer);
    writeLog($item, ItemStatus::InProgress, ItemStatus::OnHold, '2026-10-05 10:00', $programmer);
    writeLog($item, ItemStatus::OnHold, ItemStatus::InProgress, '2026-10-07 15:00', $programmer);
    writeLog($item, ItemStatus::InProgress, ItemStatus::ReadyForQa, '2026-10-07 16:00', $programmer);

    $row = kpi()->programmer($programmer, ...october())->items[0];

    expect($row['actual_days'])->toBe(0.33)->and($row['on_time'])->toBeTrue();
});

it('membobot ketepatan waktu dengan poin bila diaktifkan', function (): void {
    setSetting(SettingKey::WeightByPoints, true);
    $programmer = User::factory()->programmer()->create();
    doneItem($programmer, '2026-10-05 08:00', '2026-10-05 17:00', ['difficulty' => 5]); // 8 poin, tepat
    doneItem($programmer, '2026-10-05 08:00', '2026-10-07 17:00', ['difficulty' => 2]); // 2 poin, terlambat

    expect(kpi()->programmer($programmer, ...october())->metrics['timeliness'])->toBe(80.0);
});

it('menyesuaikan target untuk rentang sebagian bulan', function (): void {
    expect(kpi()->monthFraction(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31')))->toBe(1.0)
        ->and(round(kpi()->monthFraction(CarbonImmutable::parse('2026-09-16'), CarbonImmutable::parse('2026-10-31')), 2))->toBe(1.5);
});

it('menghitung KPI QA sesuai rumus PRD 7.4', function (): void {
    $qa = User::factory()->qa()->create();
    $programmer = User::factory()->programmer()->create();
    $manager = manager();

    $passed = Item::factory()->create(['status' => ItemStatus::Rejected, 'qa_id' => $qa->id, 'assignee_id' => $programmer->id]);
    writeLog($passed, ItemStatus::InProgress, ItemStatus::ReadyForQa, '2026-10-05 09:00', $programmer);
    writeLog($passed, ItemStatus::ReadyForQa, ItemStatus::QaPassed, '2026-10-05 13:00', $qa); // 4 jam
    writeLog($passed, ItemStatus::QaPassed, ItemStatus::Rejected, '2026-10-06 09:00', $manager); // keliru

    $failed = Item::factory()->create(['status' => ItemStatus::QaFailed, 'qa_id' => $qa->id, 'assignee_id' => $programmer->id]);
    writeLog($failed, ItemStatus::InProgress, ItemStatus::ReadyForQa, '2026-10-05 09:00', $programmer);
    writeLog($failed, ItemStatus::ReadyForQa, ItemStatus::QaFailed, '2026-10-05 17:00', $qa); // 8 jam

    $result = kpi()->qa($qa, ...october());

    expect($result->metrics)->toMatchArray([
        'decision_count' => 2,
        'pass_count' => 1,
        'fail_count' => 1,
        'avg_review_hours' => 6.0,
        'throughput' => 5.0,
        'speed' => 100.0,
        'accuracy' => 0.0,
    ])->and($result->finalScore)->toBe(31.5);
});

it('menurunkan skor kecepatan bila review melebihi target', function (): void {
    $qa = User::factory()->qa()->create();
    $programmer = User::factory()->programmer()->create();
    $item = Item::factory()->create(['status' => ItemStatus::QaFailed, 'qa_id' => $qa->id]);
    writeLog($item, ItemStatus::InProgress, ItemStatus::ReadyForQa, '2026-10-05 08:00', $programmer);
    writeLog($item, ItemStatus::ReadyForQa, ItemStatus::QaFailed, '2026-10-06 15:00', $qa); // 16 jam

    expect(kpi()->qa($qa, ...october())->metrics['speed'])->toBe(50.0);
});
