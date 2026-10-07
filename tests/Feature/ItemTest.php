<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('menampilkan detail item dengan allowed_transitions dari backend', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(manager())
        ->get("/items/{$item->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('items/show')
            ->where('item.id', $item->id)
            ->where('item.status.value', 'qa_passed')
            ->has('timeline', 5)
            ->where('allowed_transitions', [
                ['value' => 'done', 'label' => 'Setujui', 'requires_reason' => false],
                ['value' => 'rejected', 'label' => 'Tolak', 'requires_reason' => true],
                ['value' => 'cancelled', 'label' => 'Batalkan', 'requires_reason' => true],
            ])
        );
});

it('tidak memberi aksi transisi kepada QA pada item lulus QA', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(qaOf($item))
        ->get("/items/{$item->id}")
        ->assertInertia(fn (Assert $page) => $page->where('allowed_transitions', []));
});

it('melarang programmer lain melihat item di luar projectnya', function (): void {
    $item = Item::factory()->assigned()->create();

    $this->actingAs(User::factory()->programmer()->create())
        ->get("/items/{$item->id}")
        ->assertForbidden();
});

it('mengizinkan anggota project melihat item', function (): void {
    $item = Item::factory()->assigned()->create();
    $member = User::factory()->programmer()->create();
    $item->project->members()->attach($member);

    $this->actingAs($member)->get("/items/{$item->id}")->assertOk();
});

it('menjalankan transisi lewat endpoint', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(manager())
        ->from("/items/{$item->id}")
        ->post("/items/{$item->id}/transitions", ['status' => 'done'])
        ->assertRedirect("/items/{$item->id}");

    expect($item->fresh()->status)->toBe(ItemStatus::Done);
});

it('mengembalikan error validasi bila alasan kosong', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(manager())
        ->post("/items/{$item->id}/transitions", ['status' => 'rejected'])
        ->assertSessionHasErrors('reason');

    expect($item->fresh()->status)->toBe(ItemStatus::QaPassed);
});

it('mengembalikan 403 untuk transisi oleh pihak tidak berwenang', function (): void {
    $item = Item::factory()->qaPassed()->create();

    $this->actingAs(assigneeOf($item))
        ->post("/items/{$item->id}/transitions", ['status' => 'done'])
        ->assertForbidden();
});
