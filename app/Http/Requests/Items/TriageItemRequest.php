<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Triage oleh Manager: difficulty, estimasi, QA, dan (opsional) programmer.
 */
final class TriageItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'estimate_days' => ['required', 'numeric', 'min:0.5', 'max:999', 'multiple_of:0.5'],
            'qa_id' => ['required', 'integer', 'exists:users,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'difficulty' => 'difficulty',
            'estimate_days' => 'estimasi',
            'qa_id' => 'QA',
            'assignee_id' => 'programmer',
            'reason' => 'alasan',
        ];
    }
}
