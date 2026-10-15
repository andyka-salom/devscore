<?php

declare(strict_types=1);

namespace App\Actions\FeatureRequests;

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class CreateFeatureRequest
{
    /**
     * @param  array{kind: string, title: string, description?: string|null, priority: string}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(Project $project, User $requester, array $data): FeatureRequest
    {
        Gate::forUser($requester)->authorize('create', [FeatureRequest::class, $project]);

        $request = new FeatureRequest([
            ...$data,
            'project_id' => $project->id,
            'requested_by' => $requester->id,
        ]);
        $request->status = FeatureRequestStatus::Pending;
        $request->save();

        return $request;
    }
}
