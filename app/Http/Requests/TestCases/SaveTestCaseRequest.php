<?php

declare(strict_types=1);

namespace App\Http\Requests\TestCases;

use Illuminate\Foundation\Http\FormRequest;

final class SaveTestCaseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scenario' => ['required', 'string', 'max:255'],
            'steps' => ['nullable', 'string', 'max:5000'],
            'expected_result' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scenario' => 'skenario',
            'steps' => 'langkah pengujian',
            'expected_result' => 'expected result',
        ];
    }

    /**
     * @return array{scenario: string, steps: string|null, expected_result: string}
     */
    public function testCaseData(): array
    {
        return [
            'scenario' => $this->string('scenario')->trim()->toString(),
            'steps' => $this->filled('steps') ? $this->string('steps')->trim()->toString() : null,
            'expected_result' => $this->string('expected_result')->trim()->toString(),
        ];
    }
}
