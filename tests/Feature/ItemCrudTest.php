<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function memberOf(Project $project, string $role = 'programmer'): User
{
    $user = User::factory()->{$role}()->create();
    $project->members()->attach($user);

    return $user;
}

it('anggota project membuat item dengan nomor berurutan', function (): void {
    $project = Project::factory()->create(['code' => 'ERP']);
    $programmer = memberOf($project);

    foreach (['Satu', 'Dua'] as $title) {
        $this->actingAs($programmer)->post('/items', [
            'project_id' => $project->id,
            'type' => 'task',
            'title' => $title,
            'priority' => 'medium',
        ])->assertRedirect();
    }

    $items = Item::query()->with('project')->orderBy('number')->get();
    expect($items->pluck('code')->all())->toBe(['ERP-1', 'ERP-2'])
        ->and($items->first()->status)->toBe(ItemStatus::Backlog)
        ->and($items->first()->qa_id)->toBeNull()
        ->and($items->first()->statusLogs()->count())->toBe(1);
});

it('menolak pembuatan item di project yang bukan keanggotaan', function (): void {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->programmer()->create())->post('/items', [
        'project_id' => $project->id,
        'type' => 'task',
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();
});

it('admin tidak boleh membuat item', function (): void {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/items', [
        'project_id' => $project->id,
        'type' => 'task',
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();
});

it('memvalidasi field wajib', function (): void {
    $this->actingAs(manager())->post('/items', [])->assertSessionHasErrors(['project_id', 'type', 'title', 'priority']);
});

it('pembuat dapat mengubah konten item tetapi tidak statusnya', function (): void {
    $project = Project::factory()->create();
    $qa = memberOf($project, 'qa');
    $item = Item::factory()->create(['project_id' => $project->id, 'created_by' => $qa->id]);

    $this->actingAs($qa)->put("/items/{$item->id}", [
        'type' => 'bug',
        'title' => 'Judul baru',
        'priority' => 'critical',
        'steps_to_reproduce' => '1. Buka form',
        'status' => 'done',
    ])->assertRedirect("/items/{$item->id}");

    expect($item->fresh())
        ->title->toBe('Judul baru')
        ->steps_to_reproduce->toBe('1. Buka form')
        ->status->toBe(ItemStatus::Backlog);
});

it('melarang mengubah item yang sudah selesai', function (): void {
    $item = Item::factory()->done()->create();

    $this->actingAs(manager())->put("/items/{$item->id}", [
        'type' => 'task',
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();
});

it('manager menghapus item backlog, tidak untuk item yang sedang dikerjakan', function (): void {
    $backlog = Item::factory()->create();
    $active = Item::factory()->inProgress()->create();
    $manager = manager();

    $this->actingAs($manager)->delete("/items/{$backlog->id}")->assertRedirect('/items');
    $this->actingAs($manager)->delete("/items/{$active->id}")->assertForbidden();

    expect($backlog->fresh()->trashed())->toBeTrue();
});

it('memfilter dan mencari daftar item', function (): void {
    $project = Project::factory()->create(['code' => 'ERP']);
    Item::factory()->create(['project_id' => $project->id, 'title' => 'Perbaiki login']);
    Item::factory()->create(['project_id' => $project->id, 'title' => 'Laporan bulanan']);
    Item::factory()->done()->create(['project_id' => $project->id]);

    $manager = manager();

    $this->actingAs($manager)->get('/items?search=login')
        ->assertInertia(fn (Assert $page) => $page->component('items/index')->has('items.data', 1)
            ->where('items.data.0.title', 'Perbaiki login'));

    $this->actingAs($manager)->get('/items?search=ERP-2')
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.code', 'ERP-2'));

    $this->actingAs($manager)->get('/items?status=done')
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
});

it('programmer hanya melihat item dari project keanggotaannya', function (): void {
    $mine = Project::factory()->create();
    $programmer = memberOf($mine);
    Item::factory()->create(['project_id' => $mine->id]);
    Item::factory()->create();

    $this->actingAs($programmer)->get('/items')
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
});
