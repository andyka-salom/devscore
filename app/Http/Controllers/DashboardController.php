<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $activeStatuses = [ItemStatus::Assigned, ItemStatus::InProgress, ItemStatus::QaFailed, ItemStatus::Rejected];

        return Inertia::render('dashboard', [
            'stats' => Inertia::defer(fn (): array => [
                'my_active' => Item::query()
                    ->where('assignee_id', $user->id)
                    ->whereIn('status', $activeStatuses)
                    ->count(),
                'backlog' => Item::query()->where('status', ItemStatus::Backlog)->count(),
                'in_progress' => Item::query()->where('status', ItemStatus::InProgress)->count(),
                'done_this_month' => Item::query()
                    ->where('status', ItemStatus::Done)
                    ->where('approved_at', '>=', now()->startOfMonth())
                    ->count(),
            ]),
            'recent_items' => Inertia::defer(fn (): array => 
                Item::query()
                    ->with('project:id,name,code')
                    ->where(function($q) use ($user) {
                        $q->where('assignee_id', $user->id)
                          ->orWhere('created_by', $user->id)
                          ->orWhere('qa_id', $user->id);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'title' => $item->title,
                        'status' => \App\Http\Resources\EnumOption::of($item->status),
                        'priority' => \App\Http\Resources\EnumOption::of($item->priority),
                        'project' => $item->project->name,
                        'updated_at' => $item->updated_at->diffForHumans(),
                        'url' => route('items.show', $item),
                    ])->toArray()
            ),
            'active_projects' => Inertia::defer(fn (): array => 
                \App\Models\Project::query()
                    ->withCount([
                        'items as total_items',
                        'items as done_items' => fn ($q) => $q->where('status', ItemStatus::Done)
                    ])
                    ->where('status', \App\Enums\ProjectStatus::Active)
                    ->orderBy('updated_at', 'desc')
                    ->take(4)
                    ->get()
                    ->map(fn ($project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'code' => $project->code,
                        'progress' => $project->total_items > 0 ? round(($project->done_items / $project->total_items) * 100) : 0,
                        'total_items' => $project->total_items,
                        'done_items' => $project->done_items,
                        'url' => route('projects.show', $project),
                    ])->toArray()
            ),
        ]);
    }
}
