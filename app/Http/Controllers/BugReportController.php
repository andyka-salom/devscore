<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BugReports\CreateBugReport;
use App\Enums\BugReportStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Http\Requests\BugReports\SaveBugReportRequest;
use App\Http\Resources\BugReportResource;
use App\Http\Resources\FormOptions;
use App\Models\BugReport;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BugReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BugReport::class);

        $user = $request->user();
        $status = $request->enum('status', BugReportStatus::class);

        // Manager & Chief melihat semua; peran lain hanya temuan miliknya.
        $base = BugReport::query()
            ->when(! $user->hasAnyRole(Role::Manager, Role::Chief), fn ($q) => $q->where('reported_by', $user->id));

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $reports = $base
            ->with(['project:id,code,name', 'reporter:id,name', 'reviewer:id,name', 'escalator:id,name', 'item.project:id,code'])
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('bug-reports/index', [
            'reports' => BugReportResource::collection($reports),
            'filters' => ['status' => $status?->value],
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts[BugReportStatus::Pending->value] ?? 0),
                'awaiting_chief' => (int) ($counts[BugReportStatus::AwaitingChief->value] ?? 0),
                'approved' => (int) ($counts[BugReportStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[BugReportStatus::Rejected->value] ?? 0),
            ],
            'can' => ['create' => $user->can('create', BugReport::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', BugReport::class);

        return Inertia::render('bug-reports/form', [
            'options' => [
                'projects' => FormOptions::projects($request->user()),
                'priorities' => FormOptions::enum(Priority::class),
            ],
            'defaults' => ['project_id' => $request->integer('project') ?: null],
            'max_photos' => SaveBugReportRequest::MAX_PHOTOS,
        ]);
    }

    public function store(SaveBugReportRequest $request, CreateBugReport $create): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        $create->handle($project, $request->user(), $request->reportData(), $request->photos());

        Inertia::flash('success', 'Temuan bug dikirim. Menunggu persetujuan Manager.');

        return to_route('bug-reports.index');
    }
}
