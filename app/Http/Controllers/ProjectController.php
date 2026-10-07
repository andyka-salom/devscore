<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Projects\SaveProject;
use App\Enums\ItemStatus;
use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Http\Requests\Projects\SaveProjectRequest;
use App\Http\Resources\EnumOption;
use App\Http\Resources\FormOptions;
use App\Http\Resources\NoteResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserSummaryResource;
use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectLink;
use App\Services\Kpi\ItemPoints;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectController extends Controller
{
    private const array CLOSED_STATUSES = [ItemStatus::Done, ItemStatus::Cancelled];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $user = $request->user();

        $projects = Project::query()
            ->when(! $user->hasAnyRole(Role::Manager, Role::Admin), fn (Builder $query) => $query
                ->whereHas('members', fn (Builder $members) => $members->whereKey($user->id)))
            ->withCount([
                'members',
                'items',
                'items as open_items_count' => fn (Builder $query) => $query->whereNotIn('status', self::CLOSED_STATUSES),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('projects/index', [
            'projects' => ProjectResource::collection($projects)->resolve($request),
            'can' => ['create' => $user->can('create', Project::class)],
        ]);
    }

    public function timeline(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $user = $request->user();

        $projects = Project::query()
            ->when(! $user->hasAnyRole(Role::Manager, Role::Admin), fn (Builder $query) => $query
                ->whereHas('members', fn (Builder $members) => $members->whereKey($user->id)))
            ->orderBy('start_date')
            ->get();

        return Inertia::render('projects/timeline', [
            'projects' => $projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => EnumOption::of($p->status),
                'start_date' => $p->start_date?->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/form', [
            'project' => null,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(SaveProjectRequest $request, SaveProject $save): RedirectResponse
    {
        $project = $save->handle(null, $request->user(), $request->projectData());

        Inertia::flash('success', 'Project berhasil dibuat.');

        return to_route('projects.show', $project);
    }

    public function show(Request $request, Project $project, ItemPoints $points): Response
    {
        $this->authorize('view', $project);

        $user = $request->user();
        $project->load(['members:id,name,role', 'links', 'notes' => fn ($query) => $query->latest(), 'notes.user:id,name,role']);

        $items = $project->items()->get(['id', 'status', 'difficulty']);
        $countable = $items->reject(fn (Item $item): bool => $item->status === ItemStatus::Cancelled);
        $totalPoints = $countable->sum(fn (Item $item): int => $points->for($item->difficulty));
        $donePoints = $countable
            ->filter(fn (Item $item): bool => $item->status === ItemStatus::Done)
            ->sum(fn (Item $item): int => $points->for($item->difficulty));

        return Inertia::render('projects/show', [
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'description' => $project->description,
                'status' => EnumOption::of($project->status),
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'members' => UserSummaryResource::collection($project->members)->resolve($request),
                'links' => $project->links->map(fn (ProjectLink $link): array => [
                    'id' => $link->id,
                    'label' => $link->label,
                    'url' => $link->url,
                ])->all(),
            ],
            'summary' => [
                'status_counts' => collect(ItemStatus::cases())->map(fn (ItemStatus $status): array => [
                    'status' => EnumOption::of($status),
                    'count' => $items->where('status', $status)->count(),
                ])->filter(fn (array $row): bool => $row['count'] > 0)->values()->all(),
                'total_points' => $totalPoints,
                'done_points' => $donePoints,
                'progress' => $totalPoints > 0 ? round($donePoints / $totalPoints * 100, 1) : 0.0,
            ],
            'notes' => NoteResource::collection($project->notes)->resolve($request),
            'can' => [
                'update' => $user->can('update', $project),
                'add_note' => $user->can('addNote', $project),
                'create_item' => $user->can('create', [Item::class, $project]),
            ],
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load(['members:id', 'links']);

        return Inertia::render('projects/form', [
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status->value,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'member_ids' => $project->members->pluck('id')->all(),
                'links' => $project->links->map(fn (ProjectLink $link): array => [
                    'label' => $link->label,
                    'url' => $link->url,
                ])->all(),
            ],
            'options' => $this->formOptions(),
        ]);
    }

    public function update(SaveProjectRequest $request, Project $project, SaveProject $save): RedirectResponse
    {
        $save->handle($project, $request->user(), $request->projectData());

        Inertia::flash('success', 'Project berhasil diperbarui.');

        return to_route('projects.show', $project);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'statuses' => FormOptions::enum(ProjectStatus::class),
            'programmers' => FormOptions::users(Role::Programmer),
            'qas' => FormOptions::users(Role::Qa),
        ];
    }
}
