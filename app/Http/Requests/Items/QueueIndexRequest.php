<?php

declare(strict_types=1);

namespace App\Http\Requests\Items;

use App\Enums\ItemType;
use App\Queries\ItemQueueQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QueueIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ItemType::class)],
            'sort' => ['nullable', Rule::in(array_keys(ItemQueueQuery::SORTS))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'project' => ['nullable', 'integer'],
        ];
    }

    public function type(): ?ItemType
    {
        return $this->enum('type', ItemType::class);
    }

    public function sort(): string
    {
        return $this->string('sort', ItemQueueQuery::DEFAULT_SORT)->toString();
    }

    public function direction(): string
    {
        return $this->string('direction', 'asc')->toString();
    }

    public function project(): ?int
    {
        return $this->filled('project') ? $this->integer('project') : null;
    }

    /**
     * Filter aktif untuk dikirim balik ke frontend.
     *
     * @return array{type: string|null, sort: string, direction: string}
     */
    public function filters(): array
    {
        return [
            'type' => $this->type()?->value,
            'sort' => $this->sort(),
            'direction' => $this->direction(),
            'project' => $this->project(),
        ];
    }
}
