<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('seeder menghasilkan data yang bisa dipakai di semua halaman utama', function (): void {
    $this->seed(DatabaseSeeder::class);

    $manager = User::query()->where('email', 'manager@devscore.test')->sole();
    $programmer = User::query()->where('email', 'programmer@devscore.test')->sole();

    expect(Item::query()->where('status', ItemStatus::Done)->count())->toBe(30)
        ->and(Item::query()->whereNull('assignee_id')->whereNotNull('difficulty')->count())->toBeGreaterThanOrEqual(6);

    foreach (['/', '/projects', '/items', '/approval', '/qa', '/settings'] as $url) {
        $this->actingAs($manager)->get($url)->assertOk();
    }

    $this->actingAs($programmer)->get('/items/available')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('items.meta.total', fn (int $total) => $total >= 6));

    $start = CarbonImmutable::now()->subDays(90)->toDateString();
    $end = CarbonImmutable::now()->toDateString();

    $this->actingAs($manager)->get("/kpi?start={$start}&end={$end}")
        ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload->has('rows', 5)));
});
