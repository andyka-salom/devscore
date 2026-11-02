<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use App\Enums\ItemStatus;
use App\Enums\QaFinding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class TransitionItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ItemStatus::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'qa_finding' => [
                Rule::requiredIf(fn (): bool => $this->input('status') === ItemStatus::QaFailed->value),
                'nullable',
                Rule::enum(QaFinding::class),
            ],
            'attachment' => ['nullable', 'image', 'max:5120'], // max 5MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status',
            'reason' => 'alasan',
            'qa_finding' => 'kategori bug',
            'attachment' => 'lampiran',
        ];
    }

    public function targetStatus(): ItemStatus
    {
        return $this->enum('status', ItemStatus::class) ?? throw new \LogicException('Status sudah divalidasi.');
    }

    public function reason(): ?string
    {
        return $this->input('reason');
    }

    public function qaFinding(): ?QaFinding
    {
        return $this->enum('qa_finding', QaFinding::class);
    }

    public function attachment(): ?UploadedFile
    {
        return $this->file('attachment');
    }
}
