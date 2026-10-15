<?php

declare(strict_types=1);

namespace App\Http\Requests\FeatureRequests;

use App\Enums\FeatureRequestKind;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveFeatureRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'kind' => ['required', Rule::enum(FeatureRequestKind::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'priority' => ['required', Rule::enum(Priority::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'kind' => 'jenis permintaan',
            'title' => 'judul',
            'description' => 'deskripsi',
            'priority' => 'prioritas',
        ];
    }

    /**
     * @return array{kind: string, title: string, description: string|null, priority: string}
     */
    public function requestData(): array
    {
        return [
            'kind' => $this->string('kind')->toString(),
            'title' => $this->string('title')->trim()->toString(),
            'description' => $this->input('description'),
            'priority' => $this->string('priority')->toString(),
        ];
    }
}
