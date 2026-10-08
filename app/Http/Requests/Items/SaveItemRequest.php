<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use App\Enums\ItemType;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi konten item untuk create & update. `project_id` hanya dipakai saat create.
 */
final class SaveItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => [Rule::requiredIf($this->isMethod('post')), 'integer', 'exists:projects,id'],
            'type' => ['required', Rule::enum(ItemType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:20000'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'type' => 'tipe',
            'title' => 'judul',
            'description' => 'deskripsi',
            'steps_to_reproduce' => 'langkah reproduksi',
            'priority' => 'prioritas',
            'due_date' => 'due date',
            'image' => 'foto/image',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function itemData(): array
    {
        $isBug = $this->input('type') === ItemType::Bug->value;

        $data = [
            'type' => $this->string('type')->toString(),
            'title' => $this->string('title')->trim()->toString(),
            'description' => $this->input('description'),
            'steps_to_reproduce' => $isBug ? $this->input('steps_to_reproduce') : null,
            'priority' => $this->string('priority')->toString(),
            'due_date' => $this->input('due_date'),
        ];

        if ($this->hasFile('image')) {
            $data['image_path'] = $this->file('image')->store('items', 'public');
        } elseif ($this->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        return $data;
    }
}
