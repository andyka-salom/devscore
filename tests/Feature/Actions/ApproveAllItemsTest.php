<?php

declare(strict_types=1);

use App\Actions\Items\ApproveAllItems;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('menyetujui seluruh item lulus QA lewat TransitionItemStatus', function (): void {
    $passed = Item::factory()->qaPassed()->count(2)->create();
    $waiting = Item::factory()->readyForQa()->create();

    $count = app(ApproveAllItems::class)->handle(manager());

    expect($count)->toBe(2)
        ->and($passed->map(fn (Item $item) => $item->fresh()->status)->all())->toBe([ItemStatus::Done, ItemStatus::Done])
        ->and($passed->first()->fresh()->approved_at)->not->toBeNull()
        ->and($passed->first()->statusLogs()->latest('id')->firstOrFail()->to_status)->toBe(ItemStatus::Done)
        ->and($waiting->fresh()->status)->toBe(ItemStatus::ReadyForQa);
});

it('hanya menyetujui item sesuai filter project & tipe', function (): void {
    $project = Project::factory()->create();
    $match = Item::factory()->qaPassed()->bug()->create(['project_id' => $project->id]);
    $otherType = Item::factory()->qaPassed()->task()->create(['project_id' => $project->id]);
    $otherProject = Item::factory()->qaPassed()->bug()->create();

    $count = app(ApproveAllItems::class)->handle(manager(), $project->id, ItemType::Bug);

    expect($count)->toBe(1)
        ->and($match->fresh()->status)->toBe(ItemStatus::Done)
        ->and($otherType->fresh()->status)->toBe(ItemStatus::QaPassed)
        ->and($otherProject->fresh()->status)->toBe(ItemStatus::QaPassed);
});

it('manager menyetujui semua lewat endpoint', function (): void {
    Item::factory()->qaPassed()->bug()->count(2)->create();
    $task = Item::factory()->qaPassed()->task()->create();

    $this->actingAs(manager())
        ->from('/approval?type=bug')
        ->post('/approval/approve-all', ['type' => 'bug'])
        ->assertRedirect('/approval?type=bug');

    expect(Item::query()->where('status', ItemStatus::Done)->count())->toBe(2)
        ->and($task->fresh()->status)->toBe(ItemStatus::QaPassed);
});

it('hanya manager yang boleh approve all', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(User::factory()->chief()->create())->post('/approval/approve-all')->assertForbidden();
    $this->actingAs(qaOf($item))->post('/approval/approve-all')->assertForbidden();

    expect($item->fresh()->status)->toBe(ItemStatus::QaPassed);
});

it('mengirim izin approve all ke halaman approval', function (): void {
    $this->actingAs(manager())->get('/approval')
        ->assertInertia(fn (Assert $page) => $page->where('can.approve_all', true));

    $this->actingAs(User::factory()->chief()->create())->get('/approval')
        ->assertInertia(fn (Assert $page) => $page->where('can.approve_all', false));
});
