<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TestCaseResult;
use Database\Factories\TestCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Skenario pengujian pada satu item. `created_by` diisi pembuat, `tested_by` & `tested_at`
 * diisi otomatis oleh App\Actions\TestCases\RecordTestCaseResult.
 *
 * @property TestCaseResult $result
 * @property Carbon|null $tested_at
 */
#[Fillable(['scenario', 'steps', 'expected_result', 'created_by'])]
class TestCase extends Model
{
    /** @use HasFactory<TestCaseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => TestCaseResult::class,
            'tested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
