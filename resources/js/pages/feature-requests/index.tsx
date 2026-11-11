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
import type {
    FeatureRequestIndexPageProps,
    FeatureRequestRow,
    FeatureRequestStatus,
} from '@/types/feature-request';

const STATUS_TONE: Record<FeatureRequestStatus, BadgeTone> = {
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

export default function FeatureRequestIndex({ requests, filters, counts, can }: FeatureRequestIndexPageProps) {
    const updateFilters = useQueryFilters(filters);
    const [review, setReview] = useState<{ request: FeatureRequestRow; mode: ReviewMode } | null>(null);

    const columns: DataTableColumn<FeatureRequestRow>[] = [
        {
            id: 'title',
            header: 'Permintaan',
            cell: (row) => (
                <div className="leading-tight">
                    <p className="line-clamp-1 font-medium">{row.title}</p>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        {row.kind.label} · {row.project.name} · oleh {row.requester} · {formatDate(row.created_at)}
                    </p>
                    {row.description && (
                        <p className="text-muted-foreground mt-1 line-clamp-2 text-xs">{row.description}</p>
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
                            Task {row.item.code}
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
                        <Button size="sm" onClick={() => setReview({ request: row, mode: 'approve' })}>
                            Setujui
                        </Button>
                        {row.can_escalate && (
                            <Button size="sm" variant="outline" onClick={() => setReview({ request: row, mode: 'escalate' })}>
                                Teruskan ke Chief
                            </Button>
                        )}
                        <Button size="sm" variant="outline" onClick={() => setReview({ request: row, mode: 'reject' })}>
                            Tolak
                        </Button>
                    </div>
                ) : null,
        },
    ];

    return (
        <AppLayout
            title="Permintaan Fitur"
            actions={
                can.create && (
                    <Link href="/feature-requests/create" className={buttonVariants()}>
                        <Plus /> Ajukan Fitur
                    </Link>
                )
            }
        >
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Permintaan Fitur</CardTitle>
                        <CardDescription>
                            Usulan fitur tambahan atau baru per project. Yang disetujui otomatis menjadi task; Manager
                            dapat meneruskan permintaan tertentu ke Chief untuk keputusan akhir.
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
                        rows={requests.data}
                        getRowKey={(row) => row.id}
                        numberFrom={requests.meta.from}
                        emptyMessage="Belum ada permintaan fitur."
                    />
                    <Pagination paginator={requests} />
                </CardContent>
            </Card>

            {review && (
                <ReviewDialog
                    baseUrl={`/feature-requests/${review.request.id}`}
                    mode={review.mode}
                    subject="permintaan"
                    description={reviewDescription(
                        'feature',
                        review.mode,
                        review.request.title,
                        review.request.project.name,
                    )}
                    onClose={() => setReview(null)}
                />
            )}
        </AppLayout>
    );
}
