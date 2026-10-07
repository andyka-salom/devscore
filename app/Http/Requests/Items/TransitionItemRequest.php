<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use App\Enums\ItemStatus;
use Illuminate\Foundation\Http\FormRequest;
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

    public function attachment(): ?\Illuminate\Http\UploadedFile
    {
        return $this->file('attachment');
    }
}
