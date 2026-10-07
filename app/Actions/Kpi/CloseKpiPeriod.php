<?php

declare(strict_types=1);

namespace App\Actions\Kpi;

use App\Models\KpiPeriod;
use App\Models\User;
use App\Services\Kpi\KpiCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Menutup periode KPI bulanan: hasil dibekukan ke kpi_snapshots (PRD 5.6).
 */
final class CloseKpiPeriod
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(int $year, int $month, User $actor): KpiPeriod
    {
        Gate::forUser($actor)->authorize('closePeriod', KpiPeriod::class);

        return DB::transaction(function () use ($year, $month, $actor): KpiPeriod {
            $period = KpiPeriod::query()->lockForUpdate()->firstOrCreate(['year' => $year, 'month' => $month]);

            if ($period->isClosed()) {
                throw ValidationException::withMessages(['period' => 'Periode ini sudah ditutup.']);
            }

            if ($period->endsAt()->isFuture()) {
                throw ValidationException::withMessages(['period' => 'Periode baru bisa ditutup setelah bulan berakhir.']);
            }

            foreach ($this->calculator->team($period->startsAt(), $period->endsAt()) as $result) {
                $period->snapshots()->create([
                    'user_id' => $result->userId,
                    'role' => $result->role,
                    'metrics' => $result->toArray(),
                    'final_score' => $result->finalScore,
                ]);
            }

            $period->forceFill(['closed_at' => now(), 'closed_by' => $actor->id])->save();

            return $period;
        });
    }
}
