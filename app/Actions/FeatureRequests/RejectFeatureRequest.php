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

final class RejectFeatureRequest
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(FeatureRequest $request, User $reviewer, string $reason): FeatureRequest
    {
        Gate::forUser($reviewer)->authorize('review', $request);

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($request, $reviewer, $reason): FeatureRequest {
            $locked = FeatureRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['request' => 'Permintaan sudah diproses.']);
            }

            $locked->status = FeatureRequestStatus::Rejected;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = trim($reason);
            $locked->save();

            return $locked;
        });
    }
}
