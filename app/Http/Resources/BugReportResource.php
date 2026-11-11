<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BugReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Relasi project, reporter, reviewer, escalator, item wajib di-eager load.
 *
 * @mixin BugReport
 */
final class BugReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'priority' => EnumOption::of($this->priority),
            'status' => EnumOption::of($this->status),
            'project' => ['code' => $this->project->code, 'name' => $this->project->name],
            'reporter' => $this->reporter->name,
            'reviewer' => $this->reviewer?->name,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'photos' => array_map(
                static fn (string $path): string => $disk->url($path),
                $this->evidence_paths ?? [],
            ),
            'item' => $this->item === null ? null : [
                'code' => $this->item->code,
                'url' => route('items.show', $this->item),
            ],
            'escalated_by' => $this->escalator?->name,
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'escalation_note' => $this->escalation_note,
            'can_review' => ($this->isPending() && $request->user()?->can('review', $this->resource) === true)
                || ($this->isAwaitingChief() && $request->user()?->can('chiefReview', $this->resource) === true),
            'can_escalate' => $this->isPending() && $request->user()?->can('escalate', $this->resource) === true,
        ];
    }
}
