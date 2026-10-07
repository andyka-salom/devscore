<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Baris tabel item (antrian, daftar item, task tersedia). Relasi project, assignee, qa wajib di-eager load.
 *
 * @mixin Item
 */
final class QueueItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $queuedAt = $this->getAttribute('queued_at');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'type' => EnumOption::of($this->type),
            'priority' => EnumOption::of($this->priority),
            'status' => EnumOption::of($this->status),
            'difficulty' => $this->difficulty,
            'estimate_days' => $this->estimate_days === null ? null : (float) $this->estimate_days,
            'project' => ['code' => $this->project->code, 'name' => $this->project->name],
            'assignee' => $this->assignee?->name,
            'qa' => $this->qa?->name,
            'queued_at' => $queuedAt === null ? null : Carbon::parse($queuedAt)->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'due_date' => $this->due_date?->toIso8601String(),
            'menu' => $this->menu,
            'category' => $this->category,
            'is_production' => $this->is_production,
            'screenshot_path' => $this->screenshot_path,
            'url' => route('items.show', $this->resource),
        ];
    }
}
