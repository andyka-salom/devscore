<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Relasi item, creator, dan tester wajib di-eager load.
 *
 * @mixin TestCase
 */
final class TestCaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'scenario' => $this->scenario,
            'steps' => $this->steps,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'result' => EnumOption::of($this->result),
            'creator' => UserSummaryResource::make($this->creator)->resolve($request),
            'tester' => $this->tester ? UserSummaryResource::make($this->tester)->resolve($request) : null,
            'tested_at' => $this->tested_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => [
                'update' => $user !== null && $user->can('update', $this->resource),
                'delete' => $user !== null && $user->can('delete', $this->resource),
                'record_result' => $user !== null && $user->can('recordResult', $this->resource),
            ],
        ];
    }
}
