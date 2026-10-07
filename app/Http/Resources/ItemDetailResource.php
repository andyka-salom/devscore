<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail item. Relasi project, assignee, qa, creator wajib di-eager load.
 *
 * @mixin Item
 */
final class ItemDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'type' => EnumOption::of($this->type),
            'priority' => EnumOption::of($this->priority),
            'status' => EnumOption::of($this->status),
            'difficulty' => $this->difficulty,
            'estimate_days' => $this->estimate_days === null ? null : (float) $this->estimate_days,
            'due_date' => $this->due_date?->toDateString(),
            'project' => ['code' => $this->project->code, 'name' => $this->project->name],
            'assignee' => $this->assignee?->name,
            'qa' => $this->qa?->name,
            'creator' => $this->creator->name,
            'qa_fail_count' => $this->qa_fail_count,
            'reject_count' => $this->reject_count,
            'reopen_count' => $this->reopen_count,
            'started_at' => $this->started_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
