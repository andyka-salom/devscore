<?php

declare(strict_types=1);

use App\Actions\Items\AssignItem;
use App\Actions\Items\TriageItem;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('manager men-triage backlog: estimasi + QA tanpa programmer', function (): void {
    $item = Item::factory()->create();
    $qa = User::factory()->qa()->create();

    app(TriageItem::class)->handle($item, manager(), 4, 2.5, $qa->id, null);

    expect($item->fresh())
        ->difficulty->toBe(4)
        ->estimate_days->toBe('2.5')
        ->qa_id->toBe($qa->id)
        ->assignee_id->toBeNull();
});

it('menolak assignee yang bukan programmer', function (): void {
    $item = Item::factory()->create();

    app(AssignItem::class)->handle($item, manager(), User::factory()->qa()->create()->id, User::factory()->qa()->create()->id);
})->throws(ValidationException::class, 'programmer');

it('menolak QA yang bukan user QA', function (): void {
    $item = Item::factory()->create();

    app(AssignItem::class)->handle($item, manager(), null, User::factory()->programmer()->create()->id);
})->throws(ValidationException::class, 'QA');

it('menolak penugasan ulang setelah item dikerjakan', function (): void {
    $item = Item::factory()->inProgress()->create();

    app(AssignItem::class)->handle($item, manager(), User::factory()->programmer()->create()->id, $item->qa_id);
})->throws(AuthorizationException::class);

it('tidak boleh mengosongkan programmer pada item assigned', function (): void {
    $item = Item::factory()->assigned()->create();

    app(AssignItem::class)->handle($item, manager(), null, $item->qa_id);
})->throws(ValidationException::class);

it('mencatat perubahan penugasan sebagai note sistem', function (): void {
    $item = Item::factory()->create();
    $qa = User::factory()->qa()->create(['name' => 'Dewi']);

    app(AssignItem::class)->handle($item, manager(), null, $qa->id);

    expect($item->notes()->sole())
        ->is_system->toBeTrue()
        ->body->toContain('QA: Dewi');
});

it('menolak triage oleh non-manager lewat endpoint', function (): void {
    $item = Item::factory()->create();
    $qa = User::factory()->qa()->create();

    $this->actingAs($qa)
        ->put("/items/{$item->id}/triage", ['difficulty' => 2, 'estimate_days' => 1, 'qa_id' => $qa->id])
        ->assertForbidden();
});

it('memvalidasi estimasi kelipatan 0,5 di endpoint triage', function (): void {
    $item = Item::factory()->create();

    $this->actingAs(manager())
        ->put("/items/{$item->id}/triage", [
            'difficulty' => 2,
            'estimate_days' => 1.3,
            'qa_id' => User::factory()->qa()->create()->id,
        ])
        ->assertSessionHasErrors('estimate_days');
});
