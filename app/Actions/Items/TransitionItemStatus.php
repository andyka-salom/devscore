<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\User;
use App\Services\SettingRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan untuk mengubah status item (lihat CLAUDE.md, Aturan Domain 1).
 */
final class TransitionItemStatus
{
    public function __construct(private readonly SettingRepository $settings) {}

    /**
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function handle(Item $item, ItemStatus $to, User $actor, ?string $reason = null, ?\Illuminate\Http\UploadedFile $attachment = null): Item
    {
        $reason = filled($reason) ? trim((string) $reason) : null;

        return DB::transaction(function () use ($item, $to, $actor, $reason, $attachment): Item {
            $locked = Item::query()->lockForUpdate()->findOrFail($item->getKey());
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Transisi dari {$from->label()} ke {$to->label()} tidak diizinkan.",
                ]);
            }

            Gate::forUser($actor)->authorize('transition', [$locked, $to]);

            if (($error = $this->preconditionError($locked, $to)) !== null) {
                throw ValidationException::withMessages(['status' => $error]);
            }

            if ($reason === null && $from->requiresReason($to)) {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi untuk transisi ini.']);
            }

            $attachmentPath = null;
            if ($attachment !== null) {
                $attachmentPath = $attachment->store('attachments', 'public');
            }

            $this->applyCountersAndTimestamps($locked, $from, $to);
            $locked->status = $to;
            $locked->save();

            $locked->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor->id,
                'reason' => $reason,
                'attachment_path' => $attachmentPath,
            ]);

            if ($reason !== null) {
                $label = $from->isReopen($to) ? 'Dibuka kembali' : $to->label();
                $body = "{$label}: {$reason}";

                $locked->notes()->create([
                    'user_id' => $actor->id,
                    'body' => $body,
                    'is_system' => true,
                ]);
            }

            return $locked;
        });
    }

    /**
     * Transisi yang boleh dijalankan $user saat ini; dikirim ke frontend sebagai `allowed_transitions`.
     *
     * @return list<ItemStatus>
     */
    public function allowedFor(Item $item, User $user): array
    {
        $gate = Gate::forUser($user);

        return array_values(array_filter(
            $item->status->transitions(),
            fn (ItemStatus $to): bool => $gate->allows('transition', [$item, $to])
                && $this->preconditionError($item, $to) === null,
        ));
    }

    private function preconditionError(Item $item, ItemStatus $to): ?string
    {
        if ($item->status === ItemStatus::Backlog && $to === ItemStatus::Assigned
            && (! $item->isTriaged() || $item->assignee_id === null || $item->qa_id === null)) {
            return 'Difficulty, estimasi, assignee, dan QA wajib diisi sebelum item ditugaskan.';
        }

        if ($item->status->isReopen($to)) {
            $windowDays = $this->settings->int(SettingKey::ReopenWindowDays);

            if ($item->approved_at === null || $item->approved_at->lt(now()->subDays($windowDays))) {
                return "Item hanya dapat dibuka kembali maksimal {$windowDays} hari sejak selesai.";
            }
        }

        return null;
    }

    private function applyCountersAndTimestamps(Item $item, ItemStatus $from, ItemStatus $to): void
    {
        match (true) {
            $to === ItemStatus::QaFailed => $item->qa_fail_count++,
            $to === ItemStatus::Rejected => $item->reject_count++,
            $from->isReopen($to) => $item->reopen_count++,
            default => null,
        };

        if ($to === ItemStatus::InProgress && $item->started_at === null) {
            $item->started_at = now();
        }

        if ($to === ItemStatus::Done) {
            $item->approved_at = now();
        }
    }
}
