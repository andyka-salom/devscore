<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\User;
use App\Services\SettingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Manager aktif untuk test.
 */
function manager(): User
{
    return User::factory()->manager()->create();
}

/**
 * Ambil assignee item (programmer) sebagai model.
 */
function assigneeOf(Item $item): User
{
    return User::query()->findOrFail($item->assignee_id);
}

function qaOf(Item $item): User
{
    return User::query()->findOrFail($item->qa_id);
}

function setSetting(SettingKey $key, mixed $value): void
{
    app(SettingRepository::class)->set($key, $value);
}
