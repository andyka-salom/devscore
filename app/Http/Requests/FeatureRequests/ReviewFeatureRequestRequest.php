<?php

declare(strict_types=1);

namespace App\Http\Requests\FeatureRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Catatan opsional saat approve; alasan wajib saat reject (dicek di action).
 */
final class ReviewFeatureRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['note' => 'catatan'];
    }

    public function note(): ?string
    {
        $note = $this->string('note')->trim()->toString();

        return $note === '' ? null : $note;
    }
}
