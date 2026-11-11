<?php

declare(strict_types=1);

namespace App\Http\Requests\BugReports;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class SaveBugReportRequest extends FormRequest
{
    public const MAX_PHOTOS = 5;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:20000'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'title' => 'judul',
            'description' => 'deskripsi',
            'steps_to_reproduce' => 'langkah reproduksi',
            'priority' => 'prioritas',
            'photos' => 'bukti foto',
            'photos.*' => 'bukti foto',
        ];
    }

    /**
     * @return array{title: string, description: string|null, steps_to_reproduce: string|null, priority: string}
     */
    public function reportData(): array
    {
        return [
            'title' => $this->string('title')->trim()->toString(),
            'description' => $this->input('description'),
            'steps_to_reproduce' => $this->input('steps_to_reproduce'),
            'priority' => $this->string('priority')->toString(),
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function photos(): array
    {
        $photos = $this->file('photos');

        return is_array($photos) ? array_values($photos) : [];
    }
}
