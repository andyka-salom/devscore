<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Kpi\CloseKpiPeriod;
use App\Enums\Role;
use App\Http\Requests\Kpi\KpiRangeRequest;
use App\Models\KpiPeriod;
use App\Models\User;
use App\Services\Kpi\KpiReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KpiController extends Controller
{
    public function index(KpiRangeRequest $request, KpiReport $report): Response
    {
        $this->authorize('viewAny', KpiPeriod::class);

        $user = $request->user();
        $from = $request->from();
        $to = $request->to();
        $team = $user->can('viewTeam', KpiPeriod::class);

        return Inertia::render('kpi/index', [
            'filters' => $request->filters(),
            'scope' => $team ? 'team' : 'self',
            'source' => $this->source($report, $from, $to),
            'period' => $this->closablePeriod($report, $from, $to, $user),
            'rows' => Inertia::defer(fn (): array => $report->rows($from, $to, $team ? null : $user)),
        ]);
    }

    public function show(KpiRangeRequest $request, User $user, KpiReport $report): Response
    {
        $this->authorize('viewUser', [KpiPeriod::class, $user]);

        abort_unless($user->hasAnyRole(Role::Programmer, Role::Qa), 404);

        return Inertia::render('kpi/show', [
            'filters' => $request->filters(),
            'source' => $this->source($report, $request->from(), $request->to()),
            'row' => Inertia::defer(fn (): ?array => $report->rowFor($user, $request->from(), $request->to())),
        ]);
    }

    public function close(Request $request, CloseKpiPeriod $close): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $close->handle((int) $data['year'], (int) $data['month'], $request->user());

        Inertia::flash('success', 'Periode KPI ditutup. Hasil disimpan sebagai snapshot.');

        return back();
    }

    /**
     * @return array{type: string, label: string, closed_at: string|null}
     */
    private function source(KpiReport $report, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $closed = $report->closedPeriodFor($from, $to);

        return $closed === null
            ? ['type' => 'live', 'label' => 'Perhitungan langsung dari data terkini', 'closed_at' => null]
            : [
                'type' => 'snapshot',
                'label' => 'Periode '.$closed->startsAt()->locale('id')->translatedFormat('F Y').' (ditutup)',
                'closed_at' => $closed->closed_at?->toIso8601String(),
            ];
    }

    /**
     * Info periode bulanan bila rentang tepat satu bulan, untuk tombol "Tutup periode".
     *
     * @return array{year: int, month: int, label: string, closed: bool, can_close: bool}|null
     */
    private function closablePeriod(KpiReport $report, CarbonImmutable $from, CarbonImmutable $to, User $user): ?array
    {
        if (! $from->isSameDay($from->startOfMonth()) || ! $to->isSameDay($from->endOfMonth())) {
            return null;
        }

        $closed = $report->closedPeriodFor($from, $to) !== null;

        return [
            'year' => $from->year,
            'month' => $from->month,
            'label' => $from->locale('id')->translatedFormat('F Y'),
            'closed' => $closed,
            'can_close' => ! $closed && $to->endOfDay()->isPast() && $user->can('closePeriod', KpiPeriod::class),
        ];
    }
}
