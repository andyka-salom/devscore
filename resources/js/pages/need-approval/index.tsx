import { useMemo, useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { ReviewDialog, type ReviewMode } from '@/components/review-dialog';
import { SegmentedTabs } from '@/components/segmented-tabs';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { reviewDescription } from '@/lib/review-copy';
import type { Priority } from '@/types';
import type { NeedApprovalPageProps, NeedApprovalRow } from '@/types/need-approval';

const PRIORITY_TONE: Record<Priority, BadgeTone> = {
    low: 'neutral',
    medium: 'info',
    high: 'warning',
    critical: 'danger',
};

type Filter = 'all' | 'feature' | 'bug';

export default function NeedApprovalIndex({ features, bugs }: NeedApprovalPageProps) {
    const [filter, setFilter] = useState<Filter>('all');
    const [review, setReview] = useState<{ row: NeedApprovalRow; mode: ReviewMode } | null>(null);

    const rows = useMemo<NeedApprovalRow[]>(() => {
        const all: NeedApprovalRow[] = [
            ...features.map((feature) => ({
                entity: 'feature' as const,
                key: `feature-${feature.id}`,
                baseUrl: `/feature-requests/${feature.id}`,
                title: feature.title,
                description: feature.description,
                typeLabel: feature.kind.label,
                project: feature.project,
                author: feature.requester,
                priority: feature.priority,
                photos: [] as string[],
                escalated_by: feature.escalated_by,
                escalated_at: feature.escalated_at,
                escalation_note: feature.escalation_note,
            })),
            ...bugs.map((bug) => ({
                entity: 'bug' as const,
                key: `bug-${bug.id}`,
                baseUrl: `/bug-reports/${bug.id}`,
                title: bug.title,
                description: bug.description,
                typeLabel: 'Bug',
                project: bug.project,
                author: bug.reporter,
                priority: bug.priority,
                photos: bug.photos,
                escalated_by: bug.escalated_by,
                escalated_at: bug.escalated_at,
                escalation_note: bug.escalation_note,
            })),
        ];

        return all
            .filter((row) => filter === 'all' || row.entity === filter)
            .sort((a, b) => (a.escalated_at ?? '').localeCompare(b.escalated_at ?? ''));
    }, [features, bugs, filter]);

    const columns: DataTableColumn<NeedApprovalRow>[] = [
        {
            id: 'title',
            header: 'Pengajuan',
            cell: (row) => (
                <div className="leading-tight">
                    <p className="line-clamp-1 font-medium">{row.title}</p>
                    <p className="text-muted-foreground mt-0.5 text-xs">
                        {row.project.name} · oleh {row.author}
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
            id: 'type',
            header: 'Jenis',
            headerClassName: 'w-36',
            cell: (row) => <Badge tone={row.entity === 'bug' ? 'danger' : 'info'}>{row.typeLabel}</Badge>,
        },
        {
            id: 'priority',
            header: 'Prioritas',
            headerClassName: 'w-28',
            cell: (row) => <Badge tone={PRIORITY_TONE[row.priority.value]}>{row.priority.label}</Badge>,
        },
        {
            id: 'escalated',
            header: 'Diteruskan',
            headerClassName: 'w-56',
            cell: (row) => (
                <div className="text-xs leading-tight">
                    <p>
                        {row.escalated_by} · {formatDate(row.escalated_at)}
                    </p>
                    {row.escalation_note && <p className="text-muted-foreground mt-1 line-clamp-3">{row.escalation_note}</p>}
                </div>
            ),
        },
        {
            id: 'actions',
            header: <span className="sr-only">Aksi</span>,
            headerClassName: 'w-44',
            cell: (row) => (
                <div className="flex gap-2">
                    <Button size="sm" onClick={() => setReview({ row, mode: 'approve' })}>
                        Setujui
                    </Button>
                    <Button size="sm" variant="outline" onClick={() => setReview({ row, mode: 'reject' })}>
                        Tolak
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Need Approval">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Need Approval</CardTitle>
                        <CardDescription>
                            Permintaan fitur dan temuan bug yang diteruskan Manager. Keputusan Anda bersifat final:
                            disetujui otomatis menjadi task/bug di Backlog.
                        </CardDescription>
                    </div>
                    <SegmentedTabs
                        value={filter}
                        onChange={(value) => setFilter(value as Filter)}
                        tabs={[
                            { value: 'all', label: 'Semua', count: features.length + bugs.length },
                            { value: 'feature', label: 'Permintaan Fitur', count: features.length },
                            { value: 'bug', label: 'Temuan Bug', count: bugs.length },
                        ]}
                    />
                </CardHeader>
                <CardContent>
                    <DataTable
                        columns={columns}
                        rows={rows}
                        getRowKey={(row) => row.key}
                        emptyMessage="Tidak ada pengajuan yang menunggu keputusan Anda."
                    />
                </CardContent>
            </Card>

            {review && (
                <ReviewDialog
                    baseUrl={review.row.baseUrl}
                    mode={review.mode}
                    subject={review.row.entity === 'bug' ? 'temuan bug' : 'permintaan'}
                    description={reviewDescription(review.row.entity, review.mode, review.row.title, review.row.project.name)}
                    onClose={() => setReview(null)}
                />
            )}
        </AppLayout>
    );
}
