<?php

declare(strict_types=1);

namespace App\Actions\TestCases;

use App\Enums\TestCaseResult;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Mencatat hasil testing; `tested_by` & `tested_at` otomatis diisi user yang mengisi hasil.
 */
final class RecordTestCaseResult
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(TestCase $testCase, User $tester, TestCaseResult $result, ?string $actualResult): TestCase
    {
        Gate::forUser($tester)->authorize('recordResult', $testCase);

        if ($result === TestCaseResult::Untested) {
            throw ValidationException::withMessages(['result' => 'Pilih hasil Lulus atau Gagal.']);
        }

        $actualResult = filled($actualResult) ? trim((string) $actualResult) : null;

        if ($actualResult === null && $result === TestCaseResult::Failed) {
            throw ValidationException::withMessages(['actual_result' => 'Hasil aktual wajib diisi bila test gagal.']);
        }

        $testCase->forceFill([
            'result' => $result,
            'actual_result' => $actualResult,
            'tested_by' => $tester->id,
            'tested_at' => now(),
        ])->save();

        return $testCase;
    }
}
