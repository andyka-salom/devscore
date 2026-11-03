<?php

declare(strict_types=1);

namespace App\Actions\TestCases;

use App\Models\Item;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

final class CreateTestCase
{
    /**
     * @param  array{scenario: string, steps: string|null, expected_result: string}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(Item $item, User $creator, array $data): TestCase
    {
        Gate::forUser($creator)->authorize('create', [TestCase::class, $item]);

        return $item->testCases()->create([...$data, 'created_by' => $creator->id]);
    }
}
