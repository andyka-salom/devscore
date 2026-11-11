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

/**
 * Manager meneruskan temuan ke Chief; keputusan akhir (dan pembuatan bug) ada di Chief.
 */
final class EscalateBugReport
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(BugReport $report, User $manager, ?string $note = null): BugReport
    {
        Gate::forUser($manager)->authorize('escalate', $report);

        return DB::transaction(function () use ($report, $manager, $note): BugReport {
            $locked = BugReport::query()->lockForUpdate()->findOrFail($report->getKey());

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['report' => 'Temuan sudah diproses.']);
            }

            $locked->status = BugReportStatus::AwaitingChief;
            $locked->escalated_by = $manager->id;
            $locked->escalated_at = now();
            $locked->escalation_note = $note;
            $locked->save();

            return $locked;
        });
    }
}
