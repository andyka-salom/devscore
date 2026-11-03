<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris daftar test case. Relasi item.project, creator, dan tester wajib di-eager load.
 *
 * @mixin TestCase
 */
final class TestCaseRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scenario' => $this->scenario,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'result' => EnumOption::of($this->result),
            'creator' => $this->creator->name,
            'tester' => $this->tester?->name,
            'tested_at' => $this->tested_at?->toIso8601String(),
            'item' => [
                'code' => $this->item->code,
                'title' => $this->item->title,
                'status' => EnumOption::of($this->item->status),
                'project' => $this->item->project->name,
                'url' => route('items.show', $this->item),
            ],
        ];
    }
}
