<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Items\ApproveAllItems;
use App\Http\Requests\Items\QueueIndexRequest;
use App\Http\Resources\FormOptions;
use App\Http\Resources\QueueItemResource;
use App\Models\Item;
use App\Queries\ItemListQuery;
use App\Queries\ItemQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class QueueController extends Controller
{
    public function approval(QueueIndexRequest $request): Response
    {
        $this->authorize('viewApprovalQueue', Item::class);

        return $this->render('queues/approval', ItemQueueQuery::approval()->filterByProject($request->project()), $request, [
            'can' => ['approve_all' => $request->user()->can('approveAll', Item::class)],
        ]);
    }

    /**
     * Setujui seluruh item di antrian approval sesuai filter project & tipe yang aktif.
     */
    public function approveAll(QueueIndexRequest $request, ApproveAllItems $approveAll): RedirectResponse
    {
        $this->authorize('approveAll', Item::class);

        $count = $approveAll->handle($request->user(), $request->project(), $request->type());

        Inertia::flash('success', $count > 0 ? "{$count} item disetujui." : 'Tidak ada item yang menunggu approval.');

        return back();
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

    /**
     * @param  array<string, mixed>  $extra  props tambahan khusus halaman
     */
    private function render(string $page, ItemQueueQuery $query, QueueIndexRequest $request, array $extra = []): Response
    {
        return Inertia::render($page, [
            ...$extra,
            'items' => QueueItemResource::collection(
                $query->paginate($request->type(), $request->sort(), $request->direction()),
            ),
            'counts' => $query->countsByType(),
            'filters' => $request->filters(),
            'options' => [
                'projects' => FormOptions::projects($request->user()),
            ],
        ]);
    }
}
