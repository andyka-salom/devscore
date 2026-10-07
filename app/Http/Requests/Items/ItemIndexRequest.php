<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\Priority;
use App\Queries\ItemListQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ItemIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'project' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(ItemType::class)],
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'assignee' => ['nullable', 'integer'],
            'mine' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(ItemListQuery::SORTS))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array{search: string|null, project: int|null, type: string|null, status: string|null, priority: string|null, assignee: int|null, mine: bool, sort: string, direction: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            'project' => $this->filled('project') ? $this->integer('project') : null,
            'type' => $this->enum('type', ItemType::class)?->value,
            'status' => $this->enum('status', ItemStatus::class)?->value,
            'priority' => $this->enum('priority', Priority::class)?->value,
            'assignee' => $this->filled('assignee') ? $this->integer('assignee') : null,
            'mine' => $this->boolean('mine'),
            'sort' => $this->string('sort', 'updated_at')->toString(),
            'direction' => $this->string('direction', 'desc')->toString(),
        ];
    }
}
