<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\Note;
use App\Models\User;

/**
 * Timeline gabungan perubahan status + notes, urut kronologis (PRD 5.5).
 * Note sistem hasil transisi tidak ditampilkan terpisah karena alasannya sudah ada di entri status;
 * note sistem lain (mis. perubahan estimasi/penugasan) tetap ditampilkan.
 * Membutuhkan relasi statusLogs.user dan notes.user sudah di-eager load.
 */
final class ItemTimeline
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function for(Item $item, User $viewer): array
    {
        $reasons = $item->statusLogs->pluck('reason')->filter()->all();

        $statusEntries = $item->statusLogs->map(fn (ItemStatusLog $log): array => [
            'id' => "status-{$log->id}",
            'kind' => 'status',
            'note_id' => null,
            'user' => UserSummaryResource::make($log->user)->resolve(),
            'from' => $log->from_status === null ? null : EnumOption::of($log->from_status),
            'to' => EnumOption::of($log->to_status),
            'body' => $log->reason,
            'attachment_url' => $log->attachment_path ? asset('storage/' . $log->attachment_path) : null,
            'is_system' => false,
            'can_edit' => false,
            'edited' => false,
            'at' => $log->created_at?->toIso8601String(),
        ]);

        $noteEntries = $item->notes
            ->reject(fn (Note $note): bool => $note->is_system && self::isTransitionNote($note, $reasons))
            ->map(fn (Note $note): array => [
                'id' => "note-{$note->id}",
                'kind' => 'note',
                'note_id' => $note->id,
                'user' => UserSummaryResource::make($note->user)->resolve(),
                'from' => null,
                'to' => null,
                'body' => $note->body,
                'is_system' => $note->is_system,
                'can_edit' => $viewer->can('update', $note),
                'edited' => $note->updated_at !== null && $note->created_at !== null && $note->updated_at->gt($note->created_at),
                'at' => $note->created_at?->toIso8601String(),
            ]);

        return $statusEntries
            ->concat($noteEntries)
            ->sortBy('at')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private static function isTransitionNote(Note $note, array $reasons): bool
    {
        foreach ($reasons as $reason) {
            if (str_ends_with($note->body, ": {$reason}")) {
                return true;
            }
        }

        return false;
    }
}
