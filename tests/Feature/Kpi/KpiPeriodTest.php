<?php

declare(strict_types=1);

use App\Actions\Kpi\CloseKpiPeriod;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\KpiPeriod;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00', 'Asia/Jakarta'));
});

it('menutup periode dan menyimpan snapshot untuk setiap programmer & QA aktif', function (): void {
    $programmer = User::factory()->programmer()->create();
    $qa = User::factory()->qa()->create();
    User::factory()->programmer()->inactive()->create();
    Item::factory()->done()->create(['assignee_id' => $programmer->id, 'qa_id' => $qa->id, 'approved_at' => '2026-10-15 10:00']);

    $period = app(CloseKpiPeriod::class)->handle(2026, 10, manager());

    expect($period->isClosed())->toBeTrue()
        ->and($period->snapshots()->count())->toBe(2)
        ->and($period->snapshots()->where('user_id', $programmer->id)->sole()->metrics['metrics']['item_count'])->toBe(1);
});

it('snapshot tidak berubah walau data item berubah setelah periode ditutup', function (): void {
    $programmer = User::factory()->programmer()->create();
    $manager = manager();
    app(CloseKpiPeriod::class)->handle(2026, 10, $manager);

    Item::factory()->done()->create(['assignee_id' => $programmer->id, 'approved_at' => '2026-10-15 10:00']);

    $this->actingAs($manager)
        ->get('/kpi?start=2026-10-01&end=2026-10-31')
        ->assertInertia(fn (Assert $page) => $page
            ->where('source.type', 'snapshot')
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('rows.0.metrics.item_count', 0)));

    // Rentang bebas yang bukan periode resmi tetap dihitung langsung.
    $this->actingAs($manager)
        ->get('/kpi?start=2026-10-01&end=2026-10-30')
        ->assertInertia(fn (Assert $page) => $page
            ->where('source.type', 'live')
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('rows.0.metrics.item_count', 1)));
});

it('tidak bisa menutup periode dua kali', function (): void {
    $manager = manager();
    app(CloseKpiPeriod::class)->handle(2026, 10, $manager);
    app(CloseKpiPeriod::class)->handle(2026, 10, $manager);
})->throws(ValidationException::class, 'sudah ditutup');

it('tidak bisa menutup bulan yang belum berakhir', function (): void {
    app(CloseKpiPeriod::class)->handle(2026, 11, manager());
})->throws(ValidationException::class, 'setelah bulan berakhir');

it('hanya manager yang bisa menutup periode', function (): void {
    app(CloseKpiPeriod::class)->handle(2026, 10, User::factory()->admin()->create());
})->throws(AuthorizationException::class);

it('programmer hanya melihat KPI dirinya sendiri', function (): void {
    $programmer = User::factory()->programmer()->create();
    $other = User::factory()->programmer()->create();

    $this->actingAs($programmer)
        ->get('/kpi')
        ->assertInertia(fn (Assert $page) => $page
            ->component('kpi/index')
            ->where('scope', 'self')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('rows', 1)
                ->where('rows.0.user_id', $programmer->id)));

    $this->actingAs($programmer)->get("/kpi/{$other->id}")->assertForbidden();
    $this->actingAs($programmer)->get("/kpi/{$programmer->id}")->assertOk();
});

it('manager melihat KPI seluruh tim dengan filter tanggal', function (): void {
    User::factory()->programmer()->count(2)->create();
    User::factory()->qa()->create();

    $this->actingAs(manager())
        ->get('/kpi?start=2026-09-01&end=2026-10-31')
        ->assertInertia(fn (Assert $page) => $page
            ->where('scope', 'team')
            ->where('filters', ['start' => '2026-09-01', 'end' => '2026-10-31'])
            ->where('period', null)
            ->loadDeferredProps(fn (Assert $reload) => $reload->has('rows', 3)));
});

it('menawarkan tombol tutup periode untuk bulan penuh yang sudah lewat', function (): void {
    $this->actingAs(manager())
        ->get('/kpi?start=2026-10-01&end=2026-10-31')
        ->assertInertia(fn (Assert $page) => $page->where('period.can_close', true)->where('period.closed', false));
});

it('memvalidasi tanggal akhir tidak sebelum tanggal mulai', function (): void {
    $this->actingAs(manager())
        ->get('/kpi?start=2026-10-10&end=2026-10-01')
        ->assertSessionHasErrors('end');
});

it('menutup periode lewat endpoint', function (): void {
    $this->actingAs(manager())
        ->post('/kpi/periods', ['year' => 2026, 'month' => 10])
        ->assertRedirect();

    expect(KpiPeriod::query()->sole()->isClosed())->toBeTrue();
});

it('drill-down menampilkan item pembentuk KPI', function (): void {
    $programmer = User::factory()->programmer()->create();
    $item = Item::factory()->done()->create(['assignee_id' => $programmer->id, 'approved_at' => '2026-10-15 10:00']);

    $this->actingAs(manager())
        ->get("/kpi/{$programmer->id}?start=2026-10-01&end=2026-10-31")
        ->assertInertia(fn (Assert $page) => $page
            ->component('kpi/show')
            ->loadDeferredProps(fn (Assert $reload) => $reload->where('row.items.0.id', $item->id)));

    expect($item->status)->toBe(ItemStatus::Done);
});
