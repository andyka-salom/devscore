<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $year
 * @property int $month
 * @property Carbon|null $closed_at
 */
#[Fillable(['year', 'month', 'closed_at', 'closed_by'])]
class KpiPeriod extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<KpiSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(KpiSnapshot::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, $this->month, 1)->startOfDay();
    }

    public function endsAt(): CarbonImmutable
    {
        return $this->startsAt()->endOfMonth();
    }
}
