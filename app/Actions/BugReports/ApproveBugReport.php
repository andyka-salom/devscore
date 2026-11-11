<?php

declare(strict_types=1);

namespace App\Actions\BugReports;

use App\Actions\Items\CreateItem;
use App\Enums\BugReportStatus;
use App\Enums\ItemType;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Menyetujui temuan bug: otomatis membuat item bertipe bug di backlog project terkait.
 * Pembuat item adalah pelapor; foto pertama menjadi gambar item (item menyimpan satu gambar),
 * seluruh foto tetap tersedia di temuan bug.
 */
final class ApproveBugReport
{
    public function __construct(private readonly CreateItem $createItem) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(BugReport $report, User $reviewer, ?string $note = null): BugReport
    {
        $gate = Gate::forUser($reviewer);

        if (! $gate->any(['review', 'chiefReview'], $report)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($report, $reviewer, $note, $gate): BugReport {
            $locked = BugReport::query()
                ->with(['project', 'reporter'])
                ->lockForUpdate()
                ->findOrFail($report->getKey());

            if (! $locked->isPending() && ! $locked->isAwaitingChief()) {
                throw ValidationException::withMessages(['report' => 'Temuan sudah diproses.']);
            }

            $gate->authorize($locked->isAwaitingChief() ? 'chiefReview' : 'review', $locked);

            $item = $this->createItem->store($locked->project, $locked->reporter, [
                'type' => ItemType::Bug->value,
                'title' => $locked->title,
                'description' => $locked->description,
                'steps_to_reproduce' => $locked->steps_to_reproduce,
                'priority' => $locked->priority->value,
                'image_path' => $locked->evidence_paths[0] ?? null,
            ]);

            $locked->status = BugReportStatus::Approved;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = $note;
            $locked->item_id = $item->id;
            $locked->save();

            return $locked;
        });
    }
}
