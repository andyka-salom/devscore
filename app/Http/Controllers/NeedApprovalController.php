<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BugReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Http\Resources\BugReportResource;
use App\Http\Resources\FeatureRequestResource;
use App\Models\BugReport;
use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Antrian keputusan akhir Chief: permintaan fitur dan temuan bug yang diteruskan Manager.
 */
final class NeedApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewNeedApproval', FeatureRequest::class);

        $features = FeatureRequest::query()
            ->where('status', FeatureRequestStatus::AwaitingChief)
            ->with(['project:id,code,name', 'requester:id,name', 'reviewer:id,name', 'escalator:id,name', 'item.project:id,code'])
            ->oldest('escalated_at')
            ->get();

        $bugs = BugReport::query()
            ->where('status', BugReportStatus::AwaitingChief)
            ->with(['project:id,code,name', 'reporter:id,name', 'reviewer:id,name', 'escalator:id,name', 'item.project:id,code'])
            ->oldest('escalated_at')
            ->get();

        return Inertia::render('need-approval/index', [
            'features' => FeatureRequestResource::collection($features)->resolve($request),
            'bugs' => BugReportResource::collection($bugs)->resolve($request),
        ]);
    }
}
