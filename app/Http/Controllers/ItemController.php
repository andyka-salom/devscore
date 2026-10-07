<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Items\CreateItem;
use App\Actions\Items\TransitionItemStatus;
use App\Actions\Items\UpdateItem;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\Priority;
use App\Enums\Role;
use App\Http\Requests\Items\ItemIndexRequest;
use App\Http\Requests\Items\SaveItemRequest;
use App\Http\Resources\FormOptions;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemTimeline;
use App\Http\Resources\QueueItemResource;
use App\Models\Item;
use App\Models\Project;
use App\Queries\ItemListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ItemController extends Controller
{
    public function index(ItemIndexRequest $request): Response
    {
        $this->authorize('viewAny', Item::class);

        $user = $request->user();

        return Inertia::render('items/index', [
            'items' => QueueItemResource::collection((new ItemListQuery($user))->paginate($request->filters())),
            'filters' => $request->filters(),
            'options' => [
                'projects' => FormOptions::projects($user),
                'types' => FormOptions::enum(ItemType::class),
                'statuses' => FormOptions::enum(ItemStatus::class),
                'priorities' => FormOptions::enum(Priority::class),
                'programmers' => FormOptions::users(Role::Programmer),
            ],
            'can' => ['create' => $user->can('create', Item::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Item::class);

        return Inertia::render('items/form', [
            'item' => null,
            'options' => $this->formOptions($request),
            'defaults' => ['project_id' => $request->integer('project') ?: null],
        ]);
    }

    public function store(SaveItemRequest $request, CreateItem $create): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        $item = $create->handle($project, $request->user(), $request->itemData());

        Inertia::flash('success', 'Item berhasil dibuat.');

        return to_route('items.show', $item);
    }

    public function show(Request $request, Item $item, TransitionItemStatus $transitions): Response
    {
        $this->authorize('view', $item);

        $user = $request->user();

        $item->load([
            'project:id,code,name',
            'assignee:id,name',
            'qa:id,name',
            'creator:id,name',
            'statusLogs' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            'statusLogs.user:id,name,role',
            'notes.user:id,name,role',
        ]);

        $canTriage = $user->can('updateEstimate', $item);

        return Inertia::render('items/show', [
            'item' => ItemDetailResource::make($item)->resolve($request),
            'timeline' => ItemTimeline::for($item, $user),
            'allowed_transitions' => array_map(fn (ItemStatus $to): array => [
                'value' => $to->value,
                'label' => $item->status->transitionLabel($to),
                'requires_reason' => $item->status->requiresReason($to),
            ], $transitions->allowedFor($item, $user)),
            'can' => [
                'update' => $user->can('update', $item),
                'delete' => $user->can('delete', $item),
                'triage' => $canTriage,
                'assign' => $user->can('assign', $item),
                'claim' => $user->can('claim', $item),
                'add_note' => $user->can('addNote', $item),
            ],
            'triage' => $canTriage ? [
                'difficulty' => $item->difficulty,
                'estimate_days' => $item->estimate_days === null ? null : (float) $item->estimate_days,
                'assignee_id' => $item->assignee_id,
                'qa_id' => $item->qa_id,
                'locked' => $item->started_at !== null,
                'programmers' => FormOptions::users(Role::Programmer),
                'qas' => FormOptions::users(Role::Qa),
            ] : null,
        ]);
    }

    public function edit(Request $request, Item $item): Response
    {
        $this->authorize('update', $item);

        $item->load('project:id,code,name');

        return Inertia::render('items/form', [
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'project_id' => $item->project_id,
                'type' => $item->type->value,
                'title' => $item->title,
                'description' => $item->description,
                'steps_to_reproduce' => $item->steps_to_reproduce,
                'priority' => $item->priority->value,
                'due_date' => $item->due_date?->toDateString(),
            ],
            'options' => $this->formOptions($request),
            'defaults' => ['project_id' => $item->project_id],
        ]);
    }

    public function update(SaveItemRequest $request, Item $item, UpdateItem $update): RedirectResponse
    {
        $update->handle($item, $request->user(), $request->itemData());

        Inertia::flash('success', 'Item berhasil diperbarui.');

        return to_route('items.show', $item);
    }

    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        Inertia::flash('success', 'Item dihapus.');

        return to_route('items.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'projects' => FormOptions::projects($request->user()),
            'types' => FormOptions::enum(ItemType::class),
            'priorities' => FormOptions::enum(Priority::class),
        ];
    }
}
