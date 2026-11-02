import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, X } from 'lucide-react';
import type { ReactNode } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range-filter';
import { isProgrammerRow, isQaRow } from '@/components/kpi/kpi-tables';
import { KpiSourceNote } from '@/components/kpi/kpi-source-note';
import { GradeBadge, ScoreValue, scoreTone } from '@/components/kpi/score';
import { ProgressBar } from '@/components/progress-bar';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDays } from '@/lib/format';
import type { QaFinding } from '@/types/item';
import type { KpiShowPageProps, ProgrammerKpiItem, ProgrammerMetrics, QaKpiItem, TimelinessStatus } from '@/types/kpi';

const flag = (ok: boolean) =>
    ok ? (
        <Check className="size-4 text-emerald-600" aria-label="Ya" />
    ) : (
        <X className="size-4 text-red-500" aria-label="Tidak" />
    );

const TIMELINESS: Record<TimelinessStatus, { label: string; tone: BadgeTone }> = {
    on_time: { label: 'Tepat', tone: 'success' },
    late: { label: 'Terlambat', tone: 'danger' },
    pending: { label: 'Pending', tone: 'warning' },
};

const FINDING_LABEL: Record<QaFinding, string> = {
    none: 'Tanpa bug',
    minor: 'Minor',
    major: 'Major',
    critical: 'Critical',
};

const dash = <span className="text-muted-foreground">—</span>;

const programmerItemColumns: DataTableColumn<ProgrammerKpiItem>[] = [
    {
        id: 'code',
        header: 'Item',
        cell: (item) => (
            <Link href={`/items/${item.id}`} className="leading-tight hover:underline">
                <span className="block text-[13px] font-medium">{item.code}</span>
                <span className="text-muted-foreground line-clamp-1 text-xs">{item.title}</span>
            </Link>
        ),
    },
    { id: 'points', header: 'Poin', cell: (item) => item.points },
    {
        id: 'approved',
        header: 'Diterima',
        cell: (item) => (item.accepted === false ? dash : formatDate(item.approved_at)),
    },
    {
        id: 'deadline',
        header: 'Deadline / Submit',
        cell: (item) =>
            item.due_date === undefined ? (
                dash
            ) : (
                <span className="text-xs leading-tight">
                    <span className="block">{formatDate(item.due_date)}</span>
                    <span className="text-muted-foreground block">{formatDate(item.submitted_at)}</span>
                </span>
            ),
    },
    {
        id: 'on_time',
        header: 'Tepat waktu',
        cell: (item) => {
            if (item.timeliness !== undefined) {
                if (item.timeliness === null) return dash;
                const status = TIMELINESS[item.timeliness];

                return <Badge tone={status.tone}>{status.label}</Badge>;
            }

            return item.on_time === undefined ? dash : flag(item.on_time);
        },
    },
    {
        id: 'quality',
        header: 'Testing pertama',
        cell: (item) => {
            if (item.quality_score !== undefined) {
                if (item.quality_score === null || !item.qa_finding) return dash;

                return (
                    <span className="inline-flex items-center gap-2">
                        <ScoreValue value={item.quality_score} className="font-medium" />
                        <span className="text-muted-foreground text-xs">{FINDING_LABEL[item.qa_finding]}</span>
                    </span>
                );
            }

            return item.clean === undefined ? dash : flag(item.clean);
        },
    },
    { id: 'actual', header: 'Durasi kerja', cell: (item) => formatDays(item.actual_days) },
];

const qaItemColumns: DataTableColumn<QaKpiItem>[] = [
    {
        id: 'code',
        header: 'Item',
        cell: (item) => (
            <Link href={`/items/${item.id}`} className="leading-tight hover:underline">
                <span className="block text-[13px] font-medium">{item.code ?? '—'}</span>
                <span className="text-muted-foreground line-clamp-1 text-xs">{item.title}</span>
            </Link>
        ),
    },
    { id: 'decided', header: 'Tanggal', cell: (item) => formatDate(item.decided_at) },
    { id: 'decision', header: 'Keputusan', cell: (item) => (item.decision === 'qa_passed' ? 'Lulus' : 'Gagal') },
    { id: 'hours', header: 'Waktu review', cell: (item) => <ScoreValue value={item.review_hours} suffix=" jam" /> },
    {
        id: 'overturned',
        header: 'Akurat',
        cell: (item) =>
            item.decision === 'qa_passed' ? flag(!item.overturned) : <span className="text-muted-foreground">—</span>,
    },
];

export default function KpiShow({ filters, source, row }: KpiShowPageProps) {
    const programmer = row && isProgrammerRow(row) ? row : null;
    const qa = row && isQaRow(row) ? row : null;

    const changeRange = (range: { start: string; end: string }) =>
        router.get(window.location.pathname, range, { preserveScroll: true, replace: true });

    return (
        <AppLayout
            title={row ? `KPI ${row.name}` : 'KPI'}
            actions={
                <Link
                    href={`/kpi?start=${filters.start}&end=${filters.end}`}
                    className={buttonVariants({ variant: 'outline', size: 'sm' })}
                >
                    <ArrowLeft /> Kembali
                </Link>
            }
        >
            <div className="space-y-6">
                <Card>
                    <CardContent className="space-y-3 pt-4">
                        <DateRangeFilter
                            key={`${filters.start}:${filters.end}`}
                            value={filters}
                            onChange={changeRange}
                        />
                        <KpiSourceNote source={source} />
                    </CardContent>
                </Card>

                {row === undefined && <div className="bg-muted h-40 animate-pulse rounded-lg" />}
                {row === null && (
                    <Card className="text-muted-foreground p-8 text-center text-sm">
                        Data KPI tidak tersedia untuk rentang ini.
                    </Card>
                )}

                {row && (
                    <>
                        <Card>
                            <CardHeader>
                                <div>
                                    <CardTitle>{row.name}</CardTitle>
                                    <CardDescription>{row.role.label}</CardDescription>
                                </div>
                                <div className="text-right">
                                    <ScoreValue value={row.final_score} className="text-3xl font-semibold" />
                                    <div className="mt-1">
                                        <GradeBadge grade={row.grade} />
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {row.final_score === null && (
                                    <p className="text-muted-foreground mb-4 text-sm">
                                        Skor akhir tidak dihitung karena belum ada item/keputusan pada rentang ini.
                                    </p>
                                )}
                                {row.final_score !== null && row.provisional && (
                                    <p className="text-muted-foreground mb-4 text-sm">
                                        Skor sementara: ada komponen yang belum bisa dinilai (N/A) atau testing yang
                                        masih pending. Bobot komponen N/A dibagikan ke komponen lain.
                                    </p>
                                )}
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {programmer && (
                                        <>
                                            <Metric
                                                label="Produktivitas"
                                                value={programmer.metrics.productivity}
                                                detail={productivityDetail(programmer.metrics)}
                                            />
                                            <Metric
                                                label="Ketepatan waktu"
                                                value={programmer.metrics.timeliness}
                                                detail={
                                                    programmer.metrics.due_points === undefined
                                                        ? `${programmer.metrics.item_count} item selesai`
                                                        : `${programmer.metrics.on_time_points} dari ${programmer.metrics.due_points} poin jatuh tempo tepat waktu` +
                                                          (programmer.metrics.pending_points
                                                              ? ` · ${programmer.metrics.pending_points} poin pending`
                                                              : '')
                                                }
                                            />
                                            <Metric
                                                label="Kualitas"
                                                value={programmer.metrics.quality}
                                                detail={
                                                    programmer.metrics.tested_count === undefined
                                                        ? 'Tanpa gagal QA/tolak/reopen'
                                                        : `Testing pertama ${programmer.metrics.tested_count} item, dibobot poin`
                                                }
                                            />
                                        </>
                                    )}
                                    {qa && (
                                        <>
                                            <Metric
                                                label="Throughput"
                                                value={qa.metrics.throughput}
                                                detail={`${qa.metrics.decision_count} / ${qa.metrics.throughput_target} keputusan`}
                                            />
                                            <Metric
                                                label="Kecepatan review"
                                                value={qa.metrics.speed}
                                                detail={
                                                    <>
                                                        Rata-rata{' '}
                                                        <ScoreValue
                                                            value={qa.metrics.avg_review_hours}
                                                            suffix=" jam kerja"
                                                        />
                                                    </>
                                                }
                                            />
                                            <Metric
                                                label="Akurasi"
                                                value={qa.metrics.accuracy}
                                                detail={`${qa.metrics.pass_count} item diluluskan`}
                                            />
                                        </>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div>
                                    <CardTitle className="text-base">Rincian pembentuk angka</CardTitle>
                                    <CardDescription>
                                        {row.role.value === 'programmer'
                                            ? 'Item yang diterima, jatuh tempo, atau dites pertama kali pada rentang ini.'
                                            : 'Keputusan QA (lulus/gagal) pada rentang ini.'}
                                    </CardDescription>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {programmer && (
                                    <DataTable
                                        columns={programmerItemColumns}
                                        rows={programmer.items}
                                        getRowKey={(item) => item.id}
                                        numberFrom={1}
                                        emptyMessage="Belum ada item selesai."
                                    />
                                )}
                                {qa && (
                                    <DataTable
                                        columns={qaItemColumns}
                                        rows={qa.items}
                                        getRowKey={(item) => `${item.id}-${item.decided_at}`}
                                        numberFrom={1}
                                        emptyMessage="Belum ada keputusan QA."
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function productivityDetail(metrics: ProgrammerMetrics): string {
    const base = `${metrics.points} / ${metrics.target_points} poin`;
    const days = metrics.working_days === undefined ? '' : ` (${metrics.working_days} hari kerja)`;
    const actual = metrics.productivity_actual ?? null;

    return base + days + (actual !== null && actual > 100 ? ` · realisasi ${actual}%` : '');
}

function Metric({ label, value, detail }: { label: string; value: number | null; detail: ReactNode }) {
    return (
        <div className="rounded-lg border p-4">
            <p className="text-muted-foreground text-sm">{label}</p>
            <ScoreValue value={value} suffix="%" className="mt-1 block text-2xl font-semibold" />
            <ProgressBar value={value} tone={scoreTone(value)} className="mt-3" label={label} />
            <p className="text-muted-foreground mt-2 text-xs">{detail}</p>
        </div>
    );
}
