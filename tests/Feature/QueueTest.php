<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('menampilkan antrian approval untuk manager', function (): void {
    Item::factory()->qaPassed()->bug()->count(2)->create();
    Item::factory()->qaPassed()->task()->create();
    Item::factory()->readyForQa()->create();

    $this->actingAs(manager())
        ->get('/approval')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('queues/approval')
            ->has('items.data', 3)
            ->where('counts', ['all' => 3, 'bug' => 2, 'task' => 1])
            ->where('filters.sort', 'queued_at')
            ->where('nav.approval', 3)
            ->has('items.data.0', fn (Assert $row) => $row
                ->hasAll(['id', 'code', 'title', 'type', 'priority', 'status', 'difficulty', 'estimate_days',
                    'project', 'assignee', 'qa', 'queued_at', 'updated_at', 'url'])
                ->where('status.value', 'qa_passed')
            )
        );
});

it('memfilter antrian approval berdasarkan tipe', function (): void {
    Item::factory()->qaPassed()->bug()->count(2)->create();
    Item::factory()->qaPassed()->task()->create();

    $this->actingAs(manager())
        ->get('/approval?type=task')
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.type.value', 'task')
            ->where('counts.all', 3)
        );
});

it('mengurutkan antrian berdasarkan judul', function (): void {
    Item::factory()->qaPassed()->create(['title' => 'Beta']);
    Item::factory()->qaPassed()->create(['title' => 'Alpha']);
    Item::factory()->qaPassed()->create(['title' => 'Gamma']);

    $this->actingAs(manager())
        ->get('/approval?sort=title&direction=desc')
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.data.0.title', 'Gamma')
            ->where('items.data.2.title', 'Alpha')
        );
});

it('menolak parameter sort yang tidak dikenal', function (): void {
    $this->actingAs(manager())
        ->get('/approval?sort=password')
        ->assertSessionHasErrors('sort');
});

it('melarang non-manager membuka antrian approval', function (string $role): void {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get('/approval')
        ->assertForbidden();
})->with(['programmer', 'qa', 'admin']);

it('hanya menampilkan item milik QA yang login di antrian QA', function (): void {
    $mine = Item::factory()->readyForQa()->create();
    Item::factory()->readyForQa()->create();

    $this->actingAs(qaOf($mine))
        ->get('/qa')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('queues/qa')
            ->has('items.data', 1)
            ->where('items.data.0.id', $mine->id)
            ->where('nav.qa', 1)
            ->where('nav.approval', null)
        );
});

it('menampilkan seluruh antrian QA untuk manager', function (): void {
    Item::factory()->readyForQa()->count(2)->create();

    $this->actingAs(manager())
        ->get('/qa')
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 2));
});

it('melarang programmer membuka antrian QA', function (): void {
    $this->actingAs(User::factory()->programmer()->create())
        ->get('/qa')
        ->assertForbidden();
});

it('mengarahkan tamu ke halaman login', function (): void {
    $this->get('/approval')->assertRedirect('/login');
});
