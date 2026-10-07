<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Mengubah difficulty & estimasi. Setelah item pernah in_progress nilainya terkunci:
 * hanya Manager, wajib alasan, dan perubahan dicatat (CLAUDE.md, Aturan Domain 3).
 */
final class UpdateItemEstimate
{
    /**
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function handle(Item $item, User $actor, int $difficulty, float $estimateDays, ?string $reason = null): Item
    {
        Gate::forUser($actor)->authorize('updateEstimate', $item);

        $reason = filled($reason) ? trim((string) $reason) : null;

        if ($difficulty < 1 || $difficulty > 5) {
            throw ValidationException::withMessages(['difficulty' => 'Difficulty harus antara 1 dan 5.']);
        }

        if ($estimateDays <= 0 || fmod($estimateDays * 2, 1.0) !== 0.0) {
            throw ValidationException::withMessages(['estimate_days' => 'Estimasi harus kelipatan 0,5 hari.']);
        }

        return DB::transaction(function () use ($item, $actor, $difficulty, $estimateDays, $reason): Item {
            $locked = Item::query()->lockForUpdate()->findOrFail($item->getKey());
            $isLocked = $locked->started_at !== null;

            if ($isLocked && $reason === null) {
                throw ValidationException::withMessages([
                    'reason' => 'Estimasi sudah terkunci; alasan perubahan wajib diisi.',
                ]);
            }

            $before = sprintf('difficulty %s, estimasi %s hari', $locked->difficulty ?? '-', $locked->estimate_days ?? '-');

            $locked->difficulty = $difficulty;
            $locked->estimate_days = number_format($estimateDays, 1, '.', '');
            $locked->save();

            if ($isLocked) {
                $after = sprintf('difficulty %d, estimasi %s hari', $difficulty, $locked->estimate_days);

                $locked->notes()->create([
                    'user_id' => $actor->id,
                    'body' => "Estimasi diubah ({$before} → {$after}): {$reason}",
                    'is_system' => true,
                ]);
            }

            return $locked;
        });
    }
}
