import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import { ReviewDialog, type ReviewMode } from '@/components/review-dialog';
import { SegmentedTabs } from '@/components/segmented-tabs';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { reviewDescription } from '@/lib/review-copy';
import type { Priority } from '@/types';
import type { BugReportIndexPageProps, BugReportRow, BugReportStatus } from '@/types/bug-report';

const STATUS_TONE: Record<BugReportStatus, BadgeTone> = {
    pending: 'warning',
    awaiting_chief: 'info',
    approved: 'success',
    rejected: 'danger',
};

const PRIORITY_TONE: Record<Priority, BadgeTone> = {
    low: 'neutral',
    medium: 'info',
    high: 'warning',
    critical: 'danger',
};

export default function BugReportIndex({ reports, filters, counts, can }: BugReportIndexPageProps) {
    const updateFilters = useQueryFilters(filters);
    const [review, setReview] = useState<{ report: BugReportRow; mode: ReviewMode } | null>(null);

    const columns: DataTableColumn<BugReportRow>[] = [
        {
            id: 'title',
            header: 'Temuan',
            cell: (row) => (
                <div className="leading-tight">
                    <p className="line-clamp-1 font-medium">{row.title}</p>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        {row.project.name} · oleh {row.reporter} · {formatDate(row.created_at)}
                    </p>
                    {row.description && (
                        <p className="text-muted-foreground mt-1 line-clamp-2 text-xs">{row.description}</p>
                    )}
                    {row.photos.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-2">
                            {row.photos.map((url, index) => (
                                <a key={url} href={url} target="_blank" rel="noreferrer" title={`Bukti foto ${index + 1}`}>
                                    <img
                                        src={url}
                                        alt={`Bukti foto ${index + 1}`}
                                        className="h-14 w-14 rounded-md border object-cover"
                                    />
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            ),
        },
        {
            id: 'priority',
            header: 'Prioritas',
            headerClassName: 'w-28',
            cell: (row) => <Badge tone={PRIORITY_TONE[row.priority.value]}>{row.priority.label}</Badge>,
        },
        {
            id: 'status',
            header: 'Status',
            headerClassName: 'w-52',
            cell: (row) => (
                <div className="space-y-1">
                    <Badge tone={STATUS_TONE[row.status.value]}>{row.status.label}</Badge>
                    {row.escalated_by && (
                        <p className="text-muted-foreground line-clamp-2 text-xs">
                            Diteruskan {row.escalated_by}
                            {row.escalation_note ? `: ${row.escalation_note}` : ''}
                        </p>
                    )}
                    {row.item && (
                        <Link href={row.item.url} className="text-primary block text-xs hover:underline">
                            Bug {row.item.code}
                        </Link>
                    )}
                    {row.review_note && (
                        <p className="text-muted-foreground line-clamp-2 text-xs">
                            {row.reviewer}: {row.review_note}
                        </p>
                    )}
                </div>
            ),
        },
        {
            id: 'actions',
            header: <span className="sr-only">Aksi</span>,
            headerClassName: 'w-64',
            cell: (row) =>
                row.can_review ? (
                    <div className="flex flex-wrap gap-2">
                        <Button size="sm" onClick={() => setReview({ report: row, mode: 'approve' })}>
                            Setujui
                        </Button>
                        {row.can_escalate && (
                            <Button size="sm" variant="outline" onClick={() => setReview({ report: row, mode: 'escalate' })}>
                                Teruskan ke Chief
                            </Button>
                        )}
                        <Button size="sm" variant="outline" onClick={() => setReview({ report: row, mode: 'reject' })}>
                            Tolak
                        </Button>
                    </div>
                ) : null,
        },
    ];

    return (
        <AppLayout
            title="Temuan Bug"
            actions={
                can.create && (
                    <Link href="/bug-reports/create" className={buttonVariants()}>
                        <Plus /> Laporkan Bug
                    </Link>
                )
            }
        >
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Temuan Bug</CardTitle>
                        <CardDescription>
                            Laporan bug beserta bukti foto per project. Yang disetujui otomatis menjadi item bug; Manager
                            dapat meneruskan temuan tertentu ke Chief untuk keputusan akhir.
                        </CardDescription>
                    </div>
                    <SegmentedTabs
                        value={filters.status ?? 'all'}
                        onChange={(value) => updateFilters({ status: value === 'all' ? null : value })}
                        tabs={[
                            { value: 'all', label: 'Semua' },
                            { value: 'pending', label: 'Menunggu', count: counts.pending },
                            { value: 'awaiting_chief', label: 'Menunggu Chief', count: counts.awaiting_chief },
                            { value: 'approved', label: 'Disetujui' },
                            { value: 'rejected', label: 'Ditolak' },
                        ]}
                    />
                </CardHeader>
                <CardContent>
                    <DataTable
                        columns={columns}
                        rows={reports.data}
                        getRowKey={(row) => row.id}
                        numberFrom={reports.meta.from}
                        emptyMessage="Belum ada temuan bug."
                    />
                    <Pagination paginator={reports} />
                </CardContent>
            </Card>

            {review && (
                <ReviewDialog
                    baseUrl={`/bug-reports/${review.report.id}`}
                    mode={review.mode}
                    subject="temuan bug"
                    description={reviewDescription('bug', review.mode, review.report.title, review.report.project.name)}
                    onClose={() => setReview(null)}
                />
            )}
        </AppLayout>
    );
}
