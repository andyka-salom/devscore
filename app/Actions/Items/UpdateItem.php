<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

/**
 * Mengubah konten item. Status, difficulty/estimasi, dan penugasan punya Action sendiri.
 */
final class UpdateItem
{
    /**
     * @param  array{type: string, title: string, description?: string|null, steps_to_reproduce?: string|null, priority: string, due_date?: string|null}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(Item $item, User $actor, array $data): Item
    {
        Gate::forUser($actor)->authorize('update', $item);

        $item->fill([
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'steps_to_reproduce' => $data['steps_to_reproduce'] ?? null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'] ?? null,
        ])->save();

        return $item;
    }
}
