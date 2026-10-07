<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Items\TriageItem;
use App\Http\Requests\Items\TriageItemRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class ItemTriageController extends Controller
{
    public function __invoke(TriageItemRequest $request, Item $item, TriageItem $triage): RedirectResponse
    {
        $triage->handle(
            $item,
            $request->user(),
            $request->integer('difficulty'),
            (float) $request->input('estimate_days'),
            $request->integer('qa_id'),
            $request->filled('assignee_id') ? $request->integer('assignee_id') : null,
            $request->input('reason'),
        );

        Inertia::flash('success', 'Triage disimpan.');

        return back();
    }
}
