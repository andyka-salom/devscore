<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProjectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'code' => [
                Rule::requiredIf(! $project instanceof Project),
                'string',
                'max:10',
                'regex:/^[A-Za-z][A-Za-z0-9]*$/',
                Rule::unique('projects', 'code')->ignore($project instanceof Project ? $project->id : null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'links' => ['array', 'max:20'],
            'links.*.label' => ['required', 'string', 'max:100'],
            'links.*.url' => ['required', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'kode',
            'name' => 'nama',
            'description' => 'deskripsi',
            'start_date' => 'tanggal mulai',
            'end_date' => 'target selesai',
            'member_ids' => 'anggota',
            'links.*.label' => 'label link',
            'links.*.url' => 'URL link',
        ];
    }

    /**
     * @return array{code: string, name: string, description: string|null, status: string, start_date: string|null, end_date: string|null, member_ids: list<int>, links: list<array{label: string, url: string}>}
     */
    public function projectData(): array
    {
        /** @var list<array{label: string, url: string}> $links */
        $links = array_values(array_map(
            fn (array $link): array => ['label' => (string) $link['label'], 'url' => (string) $link['url']],
            $this->array('links'),
        ));

        return [
            'code' => $this->string('code')->toString(),
            'name' => $this->string('name')->toString(),
            'description' => $this->input('description'),
            'status' => $this->string('status')->toString(),
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
            'member_ids' => array_values(array_map('intval', $this->array('member_ids'))),
            'links' => $links,
        ];
    }
}
