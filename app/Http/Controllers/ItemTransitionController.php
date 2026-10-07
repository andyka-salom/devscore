<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Items\TransitionItemStatus;
use App\Http\Requests\Items\TransitionItemRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class ItemTransitionController extends Controller
{
    public function __invoke(TransitionItemRequest $request, Item $item, TransitionItemStatus $transition): RedirectResponse
    {
        $updated = $transition->handle($item, $request->targetStatus(), $request->user(), $request->reason(), $request->attachment());

        Inertia::flash('success', "Status diubah menjadi {$updated->status->label()}.");

        return back();
    }
}
