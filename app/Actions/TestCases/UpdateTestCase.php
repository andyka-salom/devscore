<?php

declare(strict_types=1);

namespace App\Actions\TestCases;

use App\Models\TestCase;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

/**
 * Mengubah isi skenario. Pembuat (`created_by`) dan hasil testing tidak berubah.
 */
final class UpdateTestCase
{
    /**
     * @param  array{scenario: string, steps: string|null, expected_result: string}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(TestCase $testCase, User $actor, array $data): TestCase
    {
        Gate::forUser($actor)->authorize('update', $testCase);

        $testCase->update($data);

        return $testCase;
    }
}
