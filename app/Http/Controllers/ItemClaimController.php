<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Items\ClaimItem;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ItemClaimController extends Controller
{
    public function __invoke(Request $request, Item $item, ClaimItem $claim): RedirectResponse
    {
        $claim->handle($item, $request->user());

        Inertia::flash('success', 'Item berhasil di-claim. Selamat bekerja!');

        return to_route('items.show', $item);
    }
}
