<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Models\Item;
use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class AddNote
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Item|Project $notable, User $author, string $body): Note
    {
        Gate::forUser($author)->authorize('addNote', $notable);

        return $notable->notes()->create([
            'user_id' => $author->id,
            'body' => trim($body),
            'is_system' => false,
        ]);
    }
}
