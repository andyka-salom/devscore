<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Relasi project, requester, reviewer, item wajib di-eager load.
 *
 * @mixin FeatureRequest
 */
final class FeatureRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'kind' => EnumOption::of($this->kind),
            'priority' => EnumOption::of($this->priority),
            'status' => EnumOption::of($this->status),
            'project' => ['code' => $this->project->code, 'name' => $this->project->name],
            'requester' => $this->requester->name,
            'reviewer' => $this->reviewer?->name,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'item' => $this->item === null ? null : [
                'code' => $this->item->code,
                'url' => route('items.show', $this->item),
            ],
            'can_review' => $this->isPending() && $request->user()?->can('review', $this->resource) === true,
        ];
    }
}
