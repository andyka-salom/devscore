<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan project untuk daftar. Butuh withCount members & items (lihat ProjectController).
 *
 * @mixin Project
 */
final class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => EnumOption::of($this->status),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'members_count' => (int) $this->getAttribute('members_count'),
            'items_count' => (int) $this->getAttribute('items_count'),
            'open_items_count' => (int) $this->getAttribute('open_items_count'),
            'url' => route('projects.show', $this->resource),
        ];
    }
}
