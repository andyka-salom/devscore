<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

/**
 * Note bisa diedit/dihapus penulisnya dalam 15 menit; note sistem tidak pernah (PRD 5.5).
 */
final class NotePolicy
{
    public const int EDIT_WINDOW_MINUTES = 15;

    public function update(User $user, Note $note): bool
    {
        return $this->isEditable($user, $note);
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->isEditable($user, $note);
    }

    private function isEditable(User $user, Note $note): bool
    {
        return $user->is_active
            && ! $note->is_system
            && $note->user_id === $user->id
            && $note->created_at !== null
            && $note->created_at->gt(now()->subMinutes(self::EDIT_WINDOW_MINUTES));
    }
}
