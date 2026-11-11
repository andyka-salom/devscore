<?php

declare(strict_types=1);

namespace App\Actions\FeatureRequests;

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Manager meneruskan permintaan ke Chief; keputusan akhir (dan pembuatan task) ada di Chief.
 */
final class EscalateFeatureRequest
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(FeatureRequest $request, User $manager, ?string $note = null): FeatureRequest
    {
        Gate::forUser($manager)->authorize('escalate', $request);

        return DB::transaction(function () use ($request, $manager, $note): FeatureRequest {
            $locked = FeatureRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['request' => 'Permintaan sudah diproses.']);
            }

            $locked->status = FeatureRequestStatus::AwaitingChief;
            $locked->escalated_by = $manager->id;
            $locked->escalated_at = now();
            $locked->escalation_note = $note;
            $locked->save();

            return $locked;
        });
    }
}
