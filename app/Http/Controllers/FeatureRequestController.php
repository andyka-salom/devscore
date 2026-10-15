<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FeatureRequests\CreateFeatureRequest;
use App\Enums\FeatureRequestKind;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Http\Requests\FeatureRequests\SaveFeatureRequestRequest;
use App\Http\Resources\FeatureRequestResource;
use App\Http\Resources\FormOptions;
use App\Models\FeatureRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FeatureRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FeatureRequest::class);

        $user = $request->user();
        $status = $request->enum('status', FeatureRequestStatus::class);

        // Manager & Chief melihat semua; peran lain hanya permintaan miliknya.
        $base = FeatureRequest::query()
            ->when(! $user->hasAnyRole(Role::Manager, Role::Chief), fn ($q) => $q->where('requested_by', $user->id));

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $requests = $base
            ->with(['project:id,code,name', 'requester:id,name', 'reviewer:id,name', 'item.project:id,code'])
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('feature-requests/index', [
            'requests' => FeatureRequestResource::collection($requests),
            'filters' => ['status' => $status?->value],
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts[FeatureRequestStatus::Pending->value] ?? 0),
                'approved' => (int) ($counts[FeatureRequestStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[FeatureRequestStatus::Rejected->value] ?? 0),
            ],
            'can' => ['create' => $user->can('create', FeatureRequest::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', FeatureRequest::class);

        return Inertia::render('feature-requests/form', [
            'options' => [
                'projects' => FormOptions::projects($request->user()),
                'kinds' => FormOptions::enum(FeatureRequestKind::class),
                'priorities' => FormOptions::enum(Priority::class),
            ],
            'defaults' => ['project_id' => $request->integer('project') ?: null],
        ]);
    }

    public function store(SaveFeatureRequestRequest $request, CreateFeatureRequest $create): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        $create->handle($project, $request->user(), $request->requestData());

        Inertia::flash('success', 'Permintaan fitur dikirim. Menunggu persetujuan Manager.');

        return to_route('feature-requests.index');
    }
}
