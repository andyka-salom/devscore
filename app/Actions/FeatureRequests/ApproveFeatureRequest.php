<?php

declare(strict_types=1);

namespace App\Actions\FeatureRequests;

use App\Actions\Items\CreateItem;
use App\Enums\FeatureRequestStatus;
use App\Enums\ItemType;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Menyetujui permintaan fitur: otomatis membuat task di backlog project terkait.
 * Pembuat task adalah pemohon agar jejak kepemilikan tetap benar.
 */
final class ApproveFeatureRequest
{
    public function __construct(private readonly CreateItem $createItem) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(FeatureRequest $request, User $reviewer, ?string $note = null): FeatureRequest
    {
        Gate::forUser($reviewer)->authorize('review', $request);

        return DB::transaction(function () use ($request, $reviewer, $note): FeatureRequest {
            $locked = FeatureRequest::query()
                ->with(['project', 'requester'])
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['request' => 'Permintaan sudah diproses.']);
            }

            $item = $this->createItem->store($locked->project, $locked->requester, [
                'type' => ItemType::Task->value,
                'title' => $locked->title,
                'description' => $locked->description,
                'priority' => $locked->priority->value,
            ]);

            $locked->status = FeatureRequestStatus::Approved;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = $note;
            $locked->item_id = $item->id;
            $locked->save();

            return $locked;
        });
    }
}
