<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Items\QueueIndexRequest;
use App\Http\Resources\QueueItemResource;
use App\Models\Item;
use App\Queries\ItemListQuery;
use App\Queries\ItemQueueQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class QueueController extends Controller
{
    public function approval(QueueIndexRequest $request): Response
    {
        $this->authorize('viewApprovalQueue', Item::class);

        return $this->render('queues/approval', ItemQueueQuery::approval()->filterByProject($request->project()), $request);
    }

    public function qa(QueueIndexRequest $request): Response
    {
        $this->authorize('viewQaQueue', Item::class);

        return $this->render('queues/qa', ItemQueueQuery::qa($request->user())->filterByProject($request->project()), $request);
    }

    /**
     * Task/bug yang siap di-claim programmer (self-assign).
     */
    public function available(Request $request): Response
    {
        $this->authorize('viewAvailable', Item::class);

        $search = $request->filled('search') ? $request->string('search')->trim()->limit(100, '')->toString() : null;

        return Inertia::render('queues/available', [
            'items' => QueueItemResource::collection((new ItemListQuery($request->user()))->available($search)),
            'filters' => ['search' => $search],
        ]);
    }

    private function render(string $page, ItemQueueQuery $query, QueueIndexRequest $request): Response
    {
        return Inertia::render($page, [
            'items' => QueueItemResource::collection(
                $query->paginate($request->type(), $request->sort(), $request->direction()),
            ),
            'counts' => $query->countsByType(),
            'filters' => $request->filters(),
            'options' => [
                'projects' => \App\Http\Resources\FormOptions::projects($request->user()),
            ],
        ]);
    }
}
