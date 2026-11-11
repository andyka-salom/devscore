<?php

declare(strict_types=1);

namespace App\Actions\BugReports;

use App\Enums\BugReportStatus;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RejectBugReport
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(BugReport $report, User $reviewer, string $reason): BugReport
    {
        $gate = Gate::forUser($reviewer);

        if (! $gate->any(['review', 'chiefReview'], $report)) {
            throw new AuthorizationException;
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($report, $reviewer, $reason, $gate): BugReport {
            $locked = BugReport::query()->lockForUpdate()->findOrFail($report->getKey());

            if (! $locked->isPending() && ! $locked->isAwaitingChief()) {
                throw ValidationException::withMessages(['report' => 'Temuan sudah diproses.']);
            }

            $gate->authorize($locked->isAwaitingChief() ? 'chiefReview' : 'review', $locked);

            $locked->status = BugReportStatus::Rejected;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = trim($reason);
            $locked->save();

            return $locked;
        });
    }
}
