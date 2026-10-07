<?php

declare(strict_types=1);

use App\Actions\Items\UpdateItemEstimate;
use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function updateEstimate(Item $item, User $actor, int $difficulty, float $days, ?string $reason = null): Item
{
    return app(UpdateItemEstimate::class)->handle($item, $actor, $difficulty, $days, $reason);
}

it('mengizinkan manager men-triage item backlog tanpa alasan', function (): void {
    $item = Item::factory()->create();

    updateEstimate($item, manager(), 3, 1.5);

    expect($item->fresh())
        ->difficulty->toBe(3)
        ->estimate_days->toBe('1.5');
    expect($item->notes()->count())->toBe(0);
});

it('menolak non-manager mengubah estimasi', function (string $role): void {
    $item = Item::factory()->create();

    updateEstimate($item, User::factory()->{$role}()->create(), 2, 1);
})->with(['programmer', 'qa', 'admin'])->throws(AuthorizationException::class);

it('mewajibkan alasan setelah item pernah in_progress', function (): void {
    $item = Item::factory()->inProgress()->create();

    updateEstimate($item, manager(), 5, 4);
})->throws(ValidationException::class, 'terkunci');

it('mencatat perubahan estimasi yang terkunci', function (): void {
    $item = Item::factory()->inProgress()->create(['difficulty' => 2, 'estimate_days' => '1.0']);

    updateEstimate($item, manager(), 4, 3, 'Scope bertambah');

    $fresh = $item->fresh();
    expect($fresh->difficulty)->toBe(4)
        ->and($fresh->estimate_days)->toBe('3.0');

    $note = $item->notes()->sole();
    expect($note->is_system)->toBeTrue()
        ->and($note->body)->toContain('difficulty 2, estimasi 1.0 hari')
        ->and($note->body)->toContain('difficulty 4, estimasi 3.0 hari')
        ->and($note->body)->toContain('Scope bertambah');
});

it('menolak estimasi bukan kelipatan 0,5', function (): void {
    updateEstimate(Item::factory()->create(), manager(), 2, 1.3);
})->throws(ValidationException::class);

it('menolak difficulty di luar 1–5', function (): void {
    updateEstimate(Item::factory()->create(), manager(), 6, 1);
})->throws(ValidationException::class);
