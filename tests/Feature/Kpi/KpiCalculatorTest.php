<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Enums\QaFinding;
use App\Enums\SettingKey;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use App\Services\Kpi\KpiCalculator;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00', 'Asia/Jakarta'));
});

function kpi(): KpiCalculator
{
    return app(KpiCalculator::class);
}

function october(): array
{
    return [CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31')];
}

function writeLog(Item $item, ?ItemStatus $from, ItemStatus $to, string $at, User $user, ?QaFinding $finding = null): void
{
    $log = new ItemStatusLog([
        'item_id' => $item->id,
        'from_status' => $from,
        'to_status' => $to,
        'user_id' => $user->id,
        'qa_finding' => $finding,
    ]);
    $log->created_at = CarbonImmutable::parse($at, 'Asia/Jakarta');
    $log->save();
}

/**
 * Item milik $programmer dengan riwayat status $history: list of [status, waktu, temuan QA?].
 * Status akhir item = status terakhir di riwayat.
 *
 * @param  list<array{0: ItemStatus, 1: string, 2?: QaFinding}>  $history
 * @param  array<string, mixed>  $attributes
 */
function programmerItem(User $programmer, array $history, array $attributes = []): Item
{
    $last = $history[array_key_last($history)];
    $doneAt = collect($history)->last(fn (array $entry): bool => $entry[0] === ItemStatus::Done)[1] ?? null;

    $item = Item::factory()->create([
        'status' => $last[0],
        'assignee_id' => $programmer->id,
        'difficulty' => 3,
        'estimate_days' => '1.0',
        'due_date' => null,
        'started_at' => $history[0][1],
        'approved_at' => $doneAt,
        ...$attributes,
    ]);

    $qa = User::factory()->qa()->create();
    $from = ItemStatus::Assigned;
    foreach ($history as $entry) {
        $actor = in_array($entry[0], [ItemStatus::QaPassed, ItemStatus::QaFailed], true) ? $qa : $programmer;
        writeLog($item, $from, $entry[0], $entry[1], $actor, $entry[2] ?? null);
        $from = $entry[0];
    }

    return $item;
}

/**
 * Item yang lulus QA pertama kali lalu diterima Manager.
 *
 * @param  array<string, mixed>  $attributes
 */
function acceptedItem(User $programmer, string $submittedAt, string $doneAt, array $attributes = [], QaFinding $finding = QaFinding::None): Item
{
    return programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, $submittedAt],
        [ItemStatus::QaPassed, $submittedAt, $finding],
        [ItemStatus::Done, $doneAt],
    ], $attributes);
}

it('menghitung KPI programmer sesuai contoh dokumen KPI', function (): void {
    // 22 hari kerja Oktober 2026 dikurangi 2 hari libur = 20 hari; target 0,5 poin/hari = 10 poin.
    Holiday::query()->create(['date' => '2026-10-01', 'name' => 'Libur 1']);
    Holiday::query()->create(['date' => '2026-10-02', 'name' => 'Libur 2']);
    setSetting(SettingKey::DailyTargetPoints, 0.5);
    $programmer = User::factory()->programmer()->create();

    // A: 5 poin, submit sebelum deadline, lolos tanpa bug → tepat waktu, kualitas 100.
    acceptedItem($programmer, '2026-10-19 10:00', '2026-10-20 09:00', ['difficulty' => 5, 'due_date' => '2026-10-20']);
    // B: 3 poin, testing pertama bug minor (85), versi yang lolos disubmit setelah deadline → terlambat.
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-09 10:00'],
        [ItemStatus::QaFailed, '2026-10-09 15:00', QaFinding::Minor],
        [ItemStatus::InProgress, '2026-10-12 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-13 10:00'],
        [ItemStatus::QaPassed, '2026-10-13 13:00', QaFinding::None],
        [ItemStatus::Done, '2026-10-14 09:00'],
    ], ['difficulty' => 3, 'due_date' => '2026-10-10']);
    // C: 2 poin, bug major (60), belum ada versi lolos QA saat deadline lewat → terlambat, belum ada poin.
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-20 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-22 09:00'],
        [ItemStatus::QaFailed, '2026-10-22 15:00', QaFinding::Major],
    ], ['difficulty' => 2, 'due_date' => '2026-10-28']);

    $result = kpi()->programmer($programmer, ...october());

    expect($result->metrics)->toMatchArray([
        'item_count' => 2,
        'points' => 8,
        'working_days' => 20,
        'target_points' => 10.0,
        'productivity_actual' => 80.0,
        'productivity' => 80.0,
        'due_points' => 10,
        'on_time_points' => 5,
        'pending_points' => 0,
        'timeliness' => 50.0,
        'tested_count' => 3,
        'quality' => 87.5,
    ])->and($result->finalScore)->toEqualWithDelta(73.25, 0.051)
        ->and($result->provisional)->toBeFalse();

    expect(collect($result->items)->pluck('quality_score')->all())->toBe([100.0, 85.0, 60.0])
        ->and(collect($result->items)->pluck('timeliness')->all())->toBe(['on_time', 'late', 'late']);
});

it('memakai poin task sama dengan difficulty', function (): void {
    setSetting(SettingKey::DailyTargetPoints, 1);
    $programmer = User::factory()->programmer()->create();
    acceptedItem($programmer, '2026-10-05 10:00', '2026-10-06 09:00', ['difficulty' => 4]);
    acceptedItem($programmer, '2026-10-05 10:00', '2026-10-06 09:00', ['difficulty' => 5]);

    expect(kpi()->programmer($programmer, ...october())->metrics['points'])->toBe(9);
});

it('memakai waktu submit programmer, bukan waktu QA selesai mengetes', function (): void {
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-20 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-24 10:00'],
        [ItemStatus::QaPassed, '2026-10-27 10:00'],
        [ItemStatus::Done, '2026-10-27 14:00'],
    ], ['due_date' => '2026-10-25']);

    $result = kpi()->programmer($programmer, ...october());

    expect($result->metrics['timeliness'])->toBe(100.0)
        ->and($result->items[0]['timeliness'])->toBe('on_time');
});

it('mengabaikan versi yang lolos QA tetapi ditolak Manager', function (): void {
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-08 10:00'],
        [ItemStatus::QaPassed, '2026-10-08 13:00'],
        [ItemStatus::Rejected, '2026-10-09 09:00'],
        [ItemStatus::InProgress, '2026-10-12 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-12 15:00'],
        [ItemStatus::QaPassed, '2026-10-13 10:00'],
        [ItemStatus::Done, '2026-10-13 14:00'],
    ], ['due_date' => '2026-10-10']);

    expect(kpi()->programmer($programmer, ...october())->items[0]['timeliness'])->toBe('late');
});

it('menandai ketepatan waktu pending bila testing belum selesai saat penutupan', function (): void {
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-20 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-30 10:00'],
    ], ['due_date' => '2026-10-31']);

    $result = kpi()->programmer($programmer, ...october());

    expect($result->items[0]['timeliness'])->toBe('pending')
        ->and($result->metrics['pending_points'])->toBe(3)
        ->and($result->metrics['timeliness'])->toBeNull()
        ->and($result->provisional)->toBeTrue();
});

it('menandai task yang deadline-nya belum lewat sebagai pending', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 09:00', 'Asia/Jakarta'));
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [[ItemStatus::InProgress, '2026-10-12 08:00']], ['due_date' => '2026-10-25']);
    programmerItem($programmer, [[ItemStatus::InProgress, '2026-10-05 08:00']], ['due_date' => '2026-10-09']);

    $result = kpi()->programmer($programmer, ...october());

    expect(collect($result->items)->pluck('timeliness')->all())->toBe(['pending', 'late'])
        ->and($result->metrics['timeliness'])->toBe(0.0);
});

it('menilai kualitas dari testing pertama yang selesai dalam rentang, termasuk yang perlu revisi', function (): void {
    $programmer = User::factory()->programmer()->create();
    // Testing pertama September → tidak masuk kualitas Oktober walau diterima Oktober.
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-09-25 08:00'],
        [ItemStatus::ReadyForQa, '2026-09-29 10:00'],
        [ItemStatus::QaFailed, '2026-09-29 15:00', QaFinding::Critical],
        [ItemStatus::InProgress, '2026-10-01 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-02 10:00'],
        [ItemStatus::QaPassed, '2026-10-02 13:00'],
        [ItemStatus::Done, '2026-10-05 09:00'],
    ], ['difficulty' => 5]);
    // Masih revisi, testing pertama Oktober dengan bug critical → 0.
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-06 10:00'],
        [ItemStatus::QaFailed, '2026-10-06 15:00', QaFinding::Critical],
        [ItemStatus::InProgress, '2026-10-07 08:00'],
    ], ['difficulty' => 1]);
    // Lolos dengan bug minor → 85.
    acceptedItem($programmer, '2026-10-08 10:00', '2026-10-09 09:00', ['difficulty' => 3], QaFinding::Minor);

    $result = kpi()->programmer($programmer, ...october());

    // (1 × 0 + 3 × 85) / 4
    expect($result->metrics['tested_count'])->toBe(2)
        ->and($result->metrics['quality'])->toBe(63.8);
});

it('memakai skor default untuk keputusan QA lama tanpa kategori bug', function (): void {
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-06 10:00'],
        [ItemStatus::QaFailed, '2026-10-06 15:00'],
    ]);

    expect(kpi()->programmer($programmer, ...october())->items[0])
        ->toMatchArray(['qa_finding' => 'major', 'quality_score' => 60.0]);
});

it('menampilkan realisasi produktivitas di atas 100% tetapi kontribusinya dibatasi 100%', function (): void {
    setSetting(SettingKey::DailyTargetPoints, 0.1); // 22 hari → 2,2 poin
    $programmer = User::factory()->programmer()->create();
    acceptedItem($programmer, '2026-10-05 10:00', '2026-10-06 09:00', ['difficulty' => 5]);

    $result = kpi()->programmer($programmer, ...october());

    expect($result->metrics['productivity_actual'])->toBe(227.3)
        ->and($result->metrics['productivity'])->toBe(100.0)
        ->and($result->finalScore)->toBe(100.0);
});

it('menampilkan N/A dan skor sementara bila tidak ada item', function (): void {
    $result = kpi()->programmer(User::factory()->programmer()->create(), ...october());

    expect($result->metrics)->toMatchArray(['item_count' => 0, 'productivity' => 0.0, 'timeliness' => null, 'quality' => null])
        ->and($result->finalScore)->toBe(0.0)
        ->and($result->provisional)->toBeTrue();
});

it('membagi bobot komponen N/A ke komponen lain', function (): void {
    setSetting(SettingKey::DailyTargetPoints, 0.5); // 11 poin
    $programmer = User::factory()->programmer()->create();
    // Tanpa deadline → ketepatan waktu N/A. Produktivitas 5/11, kualitas 85.
    acceptedItem($programmer, '2026-10-05 10:00', '2026-10-06 09:00', ['difficulty' => 5], QaFinding::Minor);

    $result = kpi()->programmer($programmer, ...october());

    // (0,4 × 45,45 + 0,3 × 85) / 0,7
    expect($result->metrics['timeliness'])->toBeNull()
        ->and($result->finalScore)->toBe(62.4)
        ->and($result->provisional)->toBeTrue();
});

it('tidak menghitung item cancelled atau item yang diterima di luar rentang', function (): void {
    $programmer = User::factory()->programmer()->create();
    Item::factory()->cancelled()->create([
        'assignee_id' => $programmer->id, 'difficulty' => 5, 'due_date' => '2026-10-10', 'approved_at' => '2026-10-10',
    ]);
    acceptedItem($programmer, '2026-09-29 10:00', '2026-09-30 10:00');

    expect(kpi()->programmer($programmer, ...october())->metrics['item_count'])->toBe(0);
});

it('tidak memberi poin lagi saat item reopen diterima ulang', function (): void {
    $programmer = User::factory()->programmer()->create();
    programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-09-20 08:00'],
        [ItemStatus::ReadyForQa, '2026-09-25 10:00'],
        [ItemStatus::QaPassed, '2026-09-25 13:00'],
        [ItemStatus::Done, '2026-09-28 09:00'],
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::ReadyForQa, '2026-10-06 10:00'],
        [ItemStatus::QaPassed, '2026-10-06 13:00'],
        [ItemStatus::Done, '2026-10-07 09:00'],
    ], ['reopen_count' => 1]);

    expect(kpi()->programmer($programmer, ...october())->metrics['points'])->toBe(0)
        ->and(kpi()->programmer($programmer, CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-30'))->metrics['points'])->toBe(3);
});

it('tidak menghitung waktu on_hold sebagai durasi kerja', function (): void {
    $programmer = User::factory()->programmer()->create();
    $item = programmerItem($programmer, [
        [ItemStatus::InProgress, '2026-10-05 08:00'],
        [ItemStatus::OnHold, '2026-10-05 10:00'],
        [ItemStatus::InProgress, '2026-10-07 15:00'],
        [ItemStatus::ReadyForQa, '2026-10-07 16:00'],
        [ItemStatus::QaPassed, '2026-10-07 16:30'],
        [ItemStatus::Done, '2026-10-20 10:00'],
    ], ['difficulty' => 2]);

    expect(kpi()->programmer($programmer, ...october())->items[0]['actual_days'])->toBe(0.33);
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
