import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, X } from 'lucide-react';
import type { ReactNode } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range-filter';
import { isProgrammerRow, isQaRow } from '@/components/kpi/kpi-tables';
import { KpiSourceNote } from '@/components/kpi/kpi-source-note';
import { GradeBadge, ScoreValue, scoreTone } from '@/components/kpi/score';
import { ProgressBar } from '@/components/progress-bar';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDays } from '@/lib/format';
import type { KpiShowPageProps, ProgrammerKpiItem, QaKpiItem } from '@/types/kpi';

const flag = (ok: boolean) =>
    ok ? (
        <Check className="size-4 text-emerald-600" aria-label="Ya" />
    ) : (
        <X className="size-4 text-red-500" aria-label="Tidak" />
    );

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
    { id: 'approved', header: 'Selesai', cell: (item) => formatDate(item.approved_at) },
    { id: 'points', header: 'Poin', cell: (item) => item.points },
    { id: 'estimate', header: 'Estimasi', cell: (item) => formatDays(item.estimate_days) },
    { id: 'actual', header: 'Aktual', cell: (item) => formatDays(item.actual_days) },
    { id: 'on_time', header: 'Tepat waktu', cell: (item) => flag(item.on_time) },
    {
        id: 'clean',
        header: 'Bersih',
        cell: (item) => (
            <span className="inline-flex items-center gap-2">
                {flag(item.clean)}
                {!item.clean && (
                    <span className="text-muted-foreground text-xs">
                        QA {item.qa_fail_count} · tolak {item.reject_count} · reopen {item.reopen_count}
                    </span>
                )}
            </span>
        ),
    },
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
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {programmer && (
                                        <>
                                            <Metric
                                                label="Produktivitas"
                                                value={programmer.metrics.productivity}
                                                detail={`${programmer.metrics.points} / ${programmer.metrics.target_points} poin`}
                                            />
                                            <Metric
                                                label="Ketepatan waktu"
                                                value={programmer.metrics.timeliness}
                                                detail={`${programmer.metrics.item_count} item selesai`}
                                            />
                                            <Metric
                                                label="Kualitas"
                                                value={programmer.metrics.quality}
                                                detail="Tanpa gagal QA/tolak/reopen"
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
                                            ? 'Item yang masuk Selesai pada rentang ini.'
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
