<?php

declare(strict_types=1);

namespace App\Actions\BugReports;

use App\Enums\BugReportStatus;
use App\Models\BugReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class CreateBugReport
{
    /**
     * @param  array{title: string, description?: string|null, steps_to_reproduce?: string|null, priority: string}  $data
     * @param  list<UploadedFile>  $photos  bukti foto (disimpan di disk public)
     *
     * @throws AuthorizationException
     */
    public function handle(Project $project, User $reporter, array $data, array $photos = []): BugReport
    {
        Gate::forUser($reporter)->authorize('create', [BugReport::class, $project]);

        $paths = array_map(
            static fn (UploadedFile $photo): string => $photo->store('bug-reports', 'public'),
            $photos,
        );

        $report = new BugReport([
            ...$data,
            'project_id' => $project->id,
            'reported_by' => $reporter->id,
            'evidence_paths' => $paths === [] ? null : $paths,
        ]);
        $report->status = BugReportStatus::Pending;
        $report->save();

        return $report;
    }
}
