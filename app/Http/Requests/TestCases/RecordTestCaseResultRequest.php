<?php

declare(strict_types=1);

namespace App\Http\Requests\TestCases;

use App\Enums\TestCaseResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordTestCaseResultRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(TestCaseResult::class)->except(TestCaseResult::Untested)],
            'actual_result' => ['nullable', 'required_if:result,failed', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'result' => 'hasil',
            'actual_result' => 'hasil aktual',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['actual_result.required_if' => 'Hasil aktual wajib diisi bila test gagal.'];
    }

    public function result(): TestCaseResult
    {
        return $this->enum('result', TestCaseResult::class) ?? TestCaseResult::Untested;
    }
}
