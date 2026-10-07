<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notes\AddNote;
use App\Http\Requests\Notes\NoteRequest;
use App\Models\Item;
use App\Models\Note;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

final class NoteController extends Controller
{
    public function storeForItem(NoteRequest $request, Item $item, AddNote $add): RedirectResponse
    {
        $add->handle($item, $request->user(), $request->string('body')->toString());

        return back();
    }

    public function storeForProject(NoteRequest $request, Project $project, AddNote $add): RedirectResponse
    {
        $add->handle($project, $request->user(), $request->string('body')->toString());

        return back();
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update(['body' => $request->string('body')->trim()->toString()]);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back();
    }
}
