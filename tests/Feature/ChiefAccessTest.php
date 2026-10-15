<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Enums\SettingKey;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\KpiPeriod;
use App\Models\Project;
use App\Models\User;
use App\Services\SettingRepository;
use Inertia\Testing\AssertableInertia as Assert;

function chief(): User
{
    return User::factory()->chief()->create();
}

it('chief memiliki label peran', function (): void {
    expect(Role::Chief->label())->toBe('Chief')
        ->and(chief()->hasRole(Role::Chief))->toBeTrue();
});

it('chief bisa membuka semua halaman utama', function (): void {
    setSetting(SettingKey::SelfAssignEnabled, true);

    $item = Item::factory()->create();
    $project = Project::query()->findOrFail($item->project_id);
    $programmer = User::factory()->programmer()->create();
    $chief = chief();

    foreach ([
        '/',
        '/projects',
        '/projects/timeline',
        "/projects/{$project->id}",
        '/items',
        "/items/{$item->id}",
        '/approval',
        '/qa',
        '/items/available',
        '/kpi',
        "/kpi/{$programmer->id}",
        '/settings',
    ] as $url) {
        $this->actingAs($chief)->get($url)->assertOk();
    }
});

it('chief melihat semua menu di sidebar', function (): void {
    setSetting(SettingKey::SelfAssignEnabled, true);

    $this->actingAs(chief())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.approval', fn (mixed $value): bool => is_int($value))
            ->where('nav.qa', fn (mixed $value): bool => is_int($value))
            ->where('nav.available', fn (mixed $value): bool => is_int($value))
            ->where('nav.kpi', true)
            ->where('nav.settings', true));
});

it('chief melihat semua project dan item tanpa menjadi anggota', function (): void {
    Item::factory()->count(2)->create();

    $this->actingAs(chief())->get('/projects')
        ->assertInertia(fn (Assert $page) => $page->has('projects', 2));
});

it('chief tidak mendapat tombol aksi di halaman detail', function (): void {
    $item = Item::factory()->create();
    $project = Project::query()->findOrFail($item->project_id);
    $chief = chief();

    $this->actingAs($chief)->get("/items/{$item->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('allowed_transitions', [])
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('can.assign', false)
            ->where('can.claim', false)
            ->where('can.add_note', false));

    $this->actingAs($chief)->get("/projects/{$project->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.update', false)
            ->where('can.add_note', false)
            ->where('can.create_item', false));

    $this->actingAs($chief)->get('/projects')
        ->assertInertia(fn (Assert $page) => $page->where('can.create', false));

    $this->actingAs($chief)->get('/items')
        ->assertInertia(fn (Assert $page) => $page->where('can.create', false));

    $this->actingAs($chief)->get('/settings')
        ->assertInertia(fn (Assert $page) => $page->where('can.manage', false));
});

it('chief ditolak untuk aksi tulis project dan item', function (): void {
    setSetting(SettingKey::SelfAssignEnabled, true);

    $item = Item::factory()->create();
    $claimable = Item::factory()->triaged()->create(['assignee_id' => null]);
    $project = Project::query()->findOrFail($item->project_id);
    $qa = User::factory()->qa()->create();
    $chief = chief();

    $this->actingAs($chief)->post('/projects', ['code' => 'X', 'name' => 'X', 'status' => 'active'])->assertForbidden();
    $this->actingAs($chief)->put("/projects/{$project->id}", ['code' => $project->code, 'name' => 'Baru', 'status' => 'active'])->assertForbidden();

    $this->actingAs($chief)->post('/items', [
        'project_id' => $project->id,
        'type' => 'task',
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();
    $this->actingAs($chief)->put("/items/{$item->id}", ['type' => 'task', 'title' => 'X', 'priority' => 'low'])->assertForbidden();
    $this->actingAs($chief)->delete("/items/{$item->id}")->assertForbidden();
    $this->actingAs($chief)->put("/items/{$item->id}/triage", [
        'difficulty' => 2,
        'estimate_days' => 1,
        'qa_id' => $qa->id,
    ])->assertForbidden();
    $this->actingAs($chief)->post("/items/{$item->id}/transitions", ['status' => 'cancelled', 'reason' => 'x'])->assertForbidden();
    $this->actingAs($chief)->post("/items/{$claimable->id}/claim")->assertForbidden();

    expect($item->fresh()->status)->toBe(ItemStatus::Backlog)
        ->and($claimable->fresh()->assignee_id)->toBeNull();
});

it('chief tidak bisa menulis catatan', function (): void {
    $item = Item::factory()->create();
    $project = Project::query()->findOrFail($item->project_id);
    $chief = chief();

    $this->actingAs($chief)->post("/items/{$item->id}/notes", ['body' => 'x'])->assertForbidden();
    $this->actingAs($chief)->post("/projects/{$project->id}/notes", ['body' => 'x'])->assertForbidden();
});

it('chief tidak bisa mengubah pengaturan atau menutup periode KPI', function (): void {
    $holiday = Holiday::query()->create(['date' => '2026-12-25', 'name' => 'Natal']);
    $chief = chief();

    $this->actingAs($chief)->put('/settings', app(SettingRepository::class)->all())->assertForbidden();
    $this->actingAs($chief)->post('/settings/holidays', ['date' => '2026-12-26', 'name' => 'Cuti'])->assertForbidden();
    $this->actingAs($chief)->post('/settings/holidays/sync')->assertForbidden();
    $this->actingAs($chief)->delete("/settings/holidays/{$holiday->id}")->assertForbidden();
    $this->actingAs($chief)->post('/kpi/periods', ['year' => 2026, 'month' => 9])->assertForbidden();

    expect(Holiday::query()->count())->toBe(1)
        ->and(KpiPeriod::query()->count())->toBe(0);
});
