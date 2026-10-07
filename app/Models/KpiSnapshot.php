<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil KPI beku untuk periode yang sudah ditutup. Tidak dihitung ulang.
 *
 * @property Role $role
 * @property array<string, mixed> $metrics
 * @property string|null $final_score
 */
#[Fillable(['kpi_period_id', 'user_id', 'role', 'metrics', 'final_score', 'note'])]
class KpiSnapshot extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'metrics' => 'array',
            'final_score' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<KpiPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(KpiPeriod::class, 'kpi_period_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
