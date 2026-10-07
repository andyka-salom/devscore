<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Relasi user wajib di-eager load.
 *
 * @mixin Note
 */
final class NoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_system' => $this->is_system,
            'user' => UserSummaryResource::make($this->user)->resolve($request),
            'created_at' => $this->created_at?->toIso8601String(),
            'edited' => $this->updated_at !== null && $this->created_at !== null && $this->updated_at->gt($this->created_at),
            'can_edit' => $user !== null && $user->can('update', $this->resource),
        ];
    }
}
