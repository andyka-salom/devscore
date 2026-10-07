import { router, usePage } from '@inertiajs/react';
import { Award, Code2, Lock, ShieldCheck, Users } from 'lucide-react';
import type { ReactNode } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DateRangeFilter } from '@/components/date-range-filter';
import { ProgrammerKpiTable, QaKpiTable, splitByRole } from '@/components/kpi/kpi-tables';
import { KpiSourceNote } from '@/components/kpi/kpi-source-note';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { KpiIndexPageProps, KpiRow } from '@/types/kpi';

function average(rows: KpiRow[]): number | null {
    const scores = rows.map((row) => row.final_score).filter((score): score is number => score !== null);

    return scores.length === 0 ? null : Math.round((scores.reduce((a, b) => a + b, 0) / scores.length) * 10) / 10;
}

export default function KpiIndex({ filters, scope, source, period, rows }: KpiIndexPageProps) {
    const { errors } = usePage().props as { errors: Record<string, string | undefined> };
    const loading = rows === undefined;
    const { programmers, qas } = splitByRole(rows ?? []);
    const top = [...(rows ?? [])].sort((a, b) => (b.final_score ?? -1) - (a.final_score ?? -1))[0];

    const changeRange = (range: { start: string; end: string }) =>
        router.get('/kpi', range, { preserveScroll: true, replace: true });

    return (
        <AppLayout title="KPI" subtitle={scope === 'team' ? 'Kinerja tim IT Development' : 'Kinerja saya'}>
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <div>
                            <CardTitle>Dashboard KPI</CardTitle>
                            <CardDescription>
                                Item dihitung berdasarkan tanggal masuk Selesai (disetujui Manager).
                            </CardDescription>
                        </div>
                        {period && <PeriodAction period={period} />}
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <DateRangeFilter
                            key={`${filters.start}:${filters.end}`}
                            value={filters}
                            onChange={changeRange}
                            error={errors.end ?? errors.start}
                        />
                        <KpiSourceNote source={source} />
                    </CardContent>
                </Card>

                {scope === 'team' && (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Rata-rata programmer"
                            value={loading ? null : (average(programmers) ?? '—')}
                            icon={Code2}
                        />
                        <StatCard
                            label="Rata-rata QA"
                            value={loading ? null : (average(qas) ?? '—')}
                            icon={ShieldCheck}
                        />
                        <StatCard
                            label="Anggota dinilai"
                            value={loading ? null : programmers.length + qas.length}
                            icon={Users}
                        />
                        <StatCard
                            label="Skor tertinggi"
                            value={loading ? null : (top?.final_score ?? '—')}
                            hint={top?.final_score != null ? top.name : undefined}
                            icon={Award}
                        />
                    </div>
                )}

                {(loading || programmers.length > 0 || scope === 'team') && (
                    <KpiCard
                        title="Programmer"
                        description="Produktivitas 40% · Tepat waktu 30% · Kualitas 30% (default)"
                        loading={loading}
                    >
                        <ProgrammerKpiTable rows={programmers} filters={filters} />
                    </KpiCard>
                )}
                {(loading || qas.length > 0 || scope === 'team') && (
                    <KpiCard
                        title="QA"
                        description="Throughput 30% · Kecepatan review 30% · Akurasi 40% (default)"
                        loading={loading}
                    >
                        <QaKpiTable rows={qas} filters={filters} />
                    </KpiCard>
                )}
            </div>
        </AppLayout>
    );
}

function KpiCard({
    title,
    description,
    loading,
    children,
}: {
    title: string;
    description: string;
    loading: boolean;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <div>
                    <CardTitle className="text-base">{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="space-y-2">
                        {[0, 1, 2].map((row) => (
                            <div key={row} className="bg-muted h-10 animate-pulse rounded" />
                        ))}
                    </div>
                ) : (
                    children
                )}
            </CardContent>
        </Card>
    );
}

function PeriodAction({ period }: { period: NonNullable<KpiIndexPageProps['period']> }) {
    if (period.closed) {
        return (
            <span className="text-muted-foreground inline-flex items-center gap-1.5 text-sm">
                <Lock className="size-4" /> {period.label} sudah ditutup
            </span>
        );
    }

    if (!period.can_close) return null;

    return (
        <ConfirmDialog
            title={`Tutup periode ${period.label}?`}
            description="Hasil KPI akan dibekukan sebagai snapshot dan tidak berubah meskipun data item berubah. Tindakan ini tidak bisa dibatalkan."
            confirmLabel="Tutup Periode"
            onConfirm={() =>
                router.post('/kpi/periods', { year: period.year, month: period.month }, { preserveScroll: true })
            }
            trigger={(open) => (
                <Button variant="outline" size="sm" onClick={open}>
                    <Lock /> Tutup periode {period.label}
                </Button>
            )}
        />
    );
}
