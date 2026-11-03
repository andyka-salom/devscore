<?php

declare(strict_types=1);

namespace App\Http\Requests\TestCases;

use App\Enums\TestCaseResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TestCaseIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'project' => ['nullable', 'integer'],
            'result' => ['nullable', Rule::enum(TestCaseResult::class)],
        ];
    }

    /**
     * @return array{search: string|null, project: int|null, result: string|null}
     */
    public function filters(): array
    {
        return [
            'search' => $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            'project' => $this->filled('project') ? $this->integer('project') : null,
            'result' => $this->enum('result', TestCaseResult::class)?->value,
        ];
    }
}
