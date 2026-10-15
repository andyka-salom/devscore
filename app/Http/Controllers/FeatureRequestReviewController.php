<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FeatureRequests\ApproveFeatureRequest;
use App\Actions\FeatureRequests\RejectFeatureRequest;
use App\Http\Requests\FeatureRequests\ReviewFeatureRequestRequest;
use App\Models\FeatureRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class FeatureRequestReviewController extends Controller
{
    public function approve(ReviewFeatureRequestRequest $request, FeatureRequest $featureRequest, ApproveFeatureRequest $approve): RedirectResponse
    {
        $approved = $approve->handle($featureRequest, $request->user(), $request->note());
        $approved->load('item.project:id,code');

        Inertia::flash('success', "Permintaan disetujui dan masuk sebagai task {$approved->item?->code}.");

        return back();
    }

    public function reject(ReviewFeatureRequestRequest $request, FeatureRequest $featureRequest, RejectFeatureRequest $reject): RedirectResponse
    {
        $reject->handle($featureRequest, $request->user(), $request->note() ?? '');

        Inertia::flash('success', 'Permintaan ditolak.');

        return back();
    }
}
