import { router } from '@inertiajs/react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { GradeBadge, ScoreValue } from '@/components/kpi/score';
import { useLocalSort } from '@/hooks/use-local-sort';
import type { KpiFilters, KpiRow } from '@/types/kpi';

type ProgrammerRow = Extract<KpiRow, { role: { value: 'programmer' } }>;
type QaRow = Extract<KpiRow, { role: { value: 'qa' } }>;

type ProgrammerSort = 'name' | 'points' | 'productivity' | 'timeliness' | 'quality' | 'final';
type QaSort = 'name' | 'decisions' | 'throughput' | 'speed' | 'accuracy' | 'final';

const programmerAccessors: Record<ProgrammerSort, (row: ProgrammerRow) => string | number | null> = {
    name: (row) => row.name,
    points: (row) => row.metrics.points,
    productivity: (row) => row.metrics.productivity,
    timeliness: (row) => row.metrics.timeliness,
    quality: (row) => row.metrics.quality,
    final: (row) => row.final_score,
};

const qaAccessors: Record<QaSort, (row: QaRow) => string | number | null> = {
    name: (row) => row.name,
    decisions: (row) => row.metrics.decision_count,
    throughput: (row) => row.metrics.throughput,
    speed: (row) => row.metrics.speed,
    accuracy: (row) => row.metrics.accuracy,
    final: (row) => row.final_score,
};

const scoreColumns = <T extends KpiRow, S extends string>(): DataTableColumn<T, S>[] => [
    {
        id: 'final',
        header: 'Skor Akhir',
        sortKey: 'final' as S,
        headerClassName: 'w-28',
        cell: (row) => <ScoreValue value={row.final_score} className="text-base font-semibold" />,
    },
    {
        id: 'grade',
        header: 'Predikat',
        headerClassName: 'w-36',
        cell: (row) => <GradeBadge grade={row.grade} />,
    },
];

const percent = (value: number | null) => <ScoreValue value={value} suffix="%" />;

const programmerColumns: DataTableColumn<ProgrammerRow, ProgrammerSort>[] = [
    {
        id: 'name',
        header: 'Programmer',
        sortKey: 'name',
        cell: (row) => <span className="font-medium">{row.name}</span>,
    },
    {
        id: 'points',
        header: 'Item / Poin',
        sortKey: 'points',
        cell: (row) => (
            <span className="tabular-nums">
                {row.metrics.item_count} item · {row.metrics.points}
                <span className="text-muted-foreground"> / {row.metrics.target_points} poin</span>
            </span>
        ),
    },
    {
        id: 'productivity',
        header: 'Produktivitas',
        sortKey: 'productivity',
        cell: (row) => percent(row.metrics.productivity),
    },
    { id: 'timeliness', header: 'Tepat Waktu', sortKey: 'timeliness', cell: (row) => percent(row.metrics.timeliness) },
    { id: 'quality', header: 'Kualitas', sortKey: 'quality', cell: (row) => percent(row.metrics.quality) },
    ...scoreColumns<ProgrammerRow, ProgrammerSort>(),
];

const qaColumns: DataTableColumn<QaRow, QaSort>[] = [
    { id: 'name', header: 'QA', sortKey: 'name', cell: (row) => <span className="font-medium">{row.name}</span> },
    {
        id: 'decisions',
        header: 'Keputusan',
        sortKey: 'decisions',
        cell: (row) => (
            <span className="tabular-nums">
                {row.metrics.decision_count}
                <span className="text-muted-foreground">
                    {' '}
                    ({row.metrics.pass_count} lulus · {row.metrics.fail_count} gagal)
                </span>
            </span>
        ),
    },
    { id: 'throughput', header: 'Throughput', sortKey: 'throughput', cell: (row) => percent(row.metrics.throughput) },
    {
        id: 'speed',
        header: 'Kecepatan',
        sortKey: 'speed',
        cell: (row) => (
            <span>
                {percent(row.metrics.speed)}
                <span className="text-muted-foreground block text-xs">
                    rata-rata <ScoreValue value={row.metrics.avg_review_hours} suffix=" jam" />
                </span>
            </span>
        ),
    },
    { id: 'accuracy', header: 'Akurasi', sortKey: 'accuracy', cell: (row) => percent(row.metrics.accuracy) },
    ...scoreColumns<QaRow, QaSort>(),
];

function openDetail(row: KpiRow, filters: KpiFilters) {
    router.visit(`/kpi/${row.user_id}?start=${filters.start}&end=${filters.end}`);
}

export function ProgrammerKpiTable({ rows, filters }: { rows: ProgrammerRow[]; filters: KpiFilters }) {
    const table = useLocalSort<ProgrammerRow, ProgrammerSort>(rows, programmerAccessors, {
        key: 'final',
        direction: 'desc',
    });

    return (
        <DataTable
            columns={programmerColumns}
            rows={table.rows}
            getRowKey={(row) => row.user_id}
            sort={table.sort}
            onSortChange={table.setSort}
            numberFrom={1}
            onRowClick={(row) => openDetail(row, filters)}
            emptyMessage="Belum ada programmer aktif."
        />
    );
}

export function QaKpiTable({ rows, filters }: { rows: QaRow[]; filters: KpiFilters }) {
    const table = useLocalSort<QaRow, QaSort>(rows, qaAccessors, { key: 'final', direction: 'desc' });

    return (
        <DataTable
            columns={qaColumns}
            rows={table.rows}
            getRowKey={(row) => row.user_id}
            sort={table.sort}
            onSortChange={table.setSort}
            numberFrom={1}
            onRowClick={(row) => openDetail(row, filters)}
            emptyMessage="Belum ada QA aktif."
        />
    );
}

export function isProgrammerRow(row: KpiRow): row is ProgrammerRow {
    return row.role.value === 'programmer';
}

export function isQaRow(row: KpiRow): row is QaRow {
    return row.role.value === 'qa';
}

export function splitByRole(rows: KpiRow[]): { programmers: ProgrammerRow[]; qas: QaRow[] } {
    return {
        programmers: rows.filter((row): row is ProgrammerRow => row.role.value === 'programmer'),
        qas: rows.filter((row): row is QaRow => row.role.value === 'qa'),
    };
}

export type { ProgrammerRow, QaRow };
