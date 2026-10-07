<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Triage Manager dalam satu form: estimasi (lewat UpdateItemEstimate) + penugasan (lewat AssignItem).
 * Hanya bagian yang berubah yang diproses.
 */
final class TriageItem
{
    public function __construct(
        private readonly UpdateItemEstimate $updateEstimate,
        private readonly AssignItem $assign,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(
        Item $item,
        User $manager,
        int $difficulty,
        float $estimateDays,
        int $qaId,
        ?int $assigneeId,
        ?string $reason = null,
    ): Item {
        return DB::transaction(function () use ($item, $manager, $difficulty, $estimateDays, $qaId, $assigneeId, $reason): Item {
            $estimateChanged = $item->difficulty !== $difficulty
                || $item->estimate_days === null
                || (float) $item->estimate_days !== $estimateDays;

            if ($estimateChanged) {
                $item = $this->updateEstimate->handle($item, $manager, $difficulty, $estimateDays, $reason);
            }

            if ($item->assignee_id !== $assigneeId || $item->qa_id !== $qaId) {
                $item = $this->assign->handle($item, $manager, $assigneeId, $qaId);
            }

            return $item;
        });
    }
}
