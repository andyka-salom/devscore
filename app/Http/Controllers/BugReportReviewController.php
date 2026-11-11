<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BugReports\ApproveBugReport;
use App\Actions\BugReports\EscalateBugReport;
use App\Actions\BugReports\RejectBugReport;
use App\Http\Requests\BugReports\ReviewBugReportRequest;
use App\Models\BugReport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class BugReportReviewController extends Controller
{
    public function approve(ReviewBugReportRequest $request, BugReport $bugReport, ApproveBugReport $approve): RedirectResponse
    {
        $approved = $approve->handle($bugReport, $request->user(), $request->note());
        $approved->load('item.project:id,code');

        Inertia::flash('success', "Temuan disetujui dan masuk sebagai bug {$approved->item?->code}.");

        return back();
    }

    public function escalate(ReviewBugReportRequest $request, BugReport $bugReport, EscalateBugReport $escalate): RedirectResponse
    {
        $escalate->handle($bugReport, $request->user(), $request->note());

        Inertia::flash('success', 'Temuan diteruskan ke Chief.');

        return back();
    }

    public function reject(ReviewBugReportRequest $request, BugReport $bugReport, RejectBugReport $reject): RedirectResponse
    {
        $reject->handle($bugReport, $request->user(), $request->note() ?? '');

        Inertia::flash('success', 'Temuan ditolak.');

        return back();
    }
}
