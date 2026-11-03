import { Link } from '@inertiajs/react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { SegmentedTabs } from '@/components/segmented-tabs';
import { StatusBadge } from '@/components/status-badge';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import { withBack } from '@/lib/back-url';
import { formatDateTime } from '@/lib/format';
import type { TestCaseIndexPageProps, TestCaseResult, TestCaseRow } from '@/types/item';

const RESULT_TONE: Record<TestCaseResult, BadgeTone> = {
    untested: 'muted',
    passed: 'success',
    failed: 'danger',
};

const columns: DataTableColumn<TestCaseRow>[] = [
    {
        id: 'scenario',
        header: 'Skenario',
        cell: (row) => (
            <div className="leading-tight">
                <p className="line-clamp-1 font-medium">{row.scenario}</p>
                <p className="text-muted-foreground mt-1 line-clamp-2 text-xs">Expected: {row.expected_result}</p>
                {row.actual_result && (
                    <p className="text-muted-foreground mt-0.5 line-clamp-2 text-xs">Aktual: {row.actual_result}</p>
                )}
            </div>
        ),
    },
    {
        id: 'item',
        header: 'Item',
        headerClassName: 'w-64',
        cell: (row) => (
            <div className="space-y-1 leading-tight">
                <Link href={withBack(row.item.url)} className="text-primary text-sm font-medium hover:underline">
                    {row.item.code}
                </Link>
                <p className="line-clamp-1 text-xs">{row.item.title}</p>
                <div className="flex items-center gap-1.5">
                    <StatusBadge status={row.item.status} />
                    <span className="text-muted-foreground line-clamp-1 text-xs">{row.item.project}</span>
                </div>
            </div>
        ),
    },
    {
        id: 'result',
        header: 'Hasil',
        headerClassName: 'w-28',
        cell: (row) => <Badge tone={RESULT_TONE[row.result.value]}>{row.result.label}</Badge>,
    },
    {
        id: 'people',
        header: 'Dibuat / Dites oleh',
        headerClassName: 'w-52',
        cell: (row) => (
            <div className="space-y-0.5 text-xs">
                <p>
                    <span className="text-muted-foreground">Dibuat:</span> {row.creator}
                </p>
                <p>
                    <span className="text-muted-foreground">Dites:</span>{' '}
                    {row.tester ? `${row.tester} · ${formatDateTime(row.tested_at)}` : '—'}
                </p>
            </div>
        ),
    },
];

export default function TestCaseIndex({ testCases, counts, filters, options }: TestCaseIndexPageProps) {
    const updateFilters = useQueryFilters(filters);

    return (
        <AppLayout title="Test Case">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Daftar Test Case</CardTitle>
                        <CardDescription>
                            Seluruh skenario pengujian dari item yang dapat Anda akses. Tambah atau isi hasil test case dari
                            halaman detail item.
                        </CardDescription>
                    </div>
                    <SegmentedTabs
                        value={filters.result ?? 'all'}
                        onChange={(value) => updateFilters({ result: value === 'all' ? null : value })}
                        tabs={[
                            { value: 'all', label: 'Semua', count: counts.all },
                            { value: 'untested', label: 'Belum dites', count: counts.untested },
                            { value: 'passed', label: 'Lulus', count: counts.passed },
                            { value: 'failed', label: 'Gagal', count: counts.failed },
                        ]}
                    />
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-3">
                        <SearchInput
                            className="sm:col-span-2"
                            value={filters.search}
                            onSearch={(search) => updateFilters({ search })}
                            placeholder="Cari skenario"
                        />
                        <Select
                            aria-label="Project"
                            options={options.projects}
                            value={filters.project}
                            onValueChange={(project) => updateFilters({ project })}
                            placeholder="Semua project"
                        />
                    </div>
                    <DataTable
                        columns={columns}
                        rows={testCases.data}
                        getRowKey={(row) => row.id}
                        numberFrom={testCases.meta.from}
                        emptyMessage="Belum ada test case."
                    />
                    <Pagination paginator={testCases} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
