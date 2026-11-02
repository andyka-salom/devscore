<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris tabel manajemen user.
 *
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => EnumOption::of($this->role),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'can_delete' => $request->user()?->can('delete', $this->resource) === true,
        ];
    }
}
