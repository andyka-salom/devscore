import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import { SegmentedTabs } from '@/components/segmented-tabs';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/input';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import type { Priority } from '@/types';
import type {
    FeatureRequestIndexPageProps,
    FeatureRequestRow,
    FeatureRequestStatus,
} from '@/types/feature-request';

const STATUS_TONE: Record<FeatureRequestStatus, BadgeTone> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
};

const PRIORITY_TONE: Record<Priority, BadgeTone> = {
    low: 'neutral',
    medium: 'info',
    high: 'warning',
    critical: 'danger',
};

type ReviewMode = 'approve' | 'reject';

function ReviewDialog({ request, mode, onClose }: { request: FeatureRequestRow; mode: ReviewMode; onClose: () => void }) {
    const [note, setNote] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const approving = mode === 'approve';

    const submit = () => {
        if (!approving && note.trim() === '') {
            setError('Alasan penolakan wajib diisi.');
            return;
        }

        router.post(
            `/feature-requests/${request.id}/${mode}`,
            { note },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: onClose,
                onError: (errors) => setError(errors.note ?? errors.reason ?? errors.request),
            },
        );
    };

    return (
        <Dialog
            open
            onClose={onClose}
            title={approving ? 'Setujui permintaan' : 'Tolak permintaan'}
            description={
                approving
                    ? `“${request.title}” akan otomatis dibuat sebagai task di Backlog ${request.project.name}.`
                    : `“${request.title}” akan ditolak dan tidak masuk ke task.`
            }
        >
            <div className="space-y-3">
                <Textarea
                    rows={3}
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    placeholder={approving ? 'Catatan (opsional)' : 'Alasan penolakan'}
                    aria-invalid={!!error}
                />
                {error && <p className="text-destructive text-sm">{error}</p>}
                <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button variant={approving ? 'default' : 'destructive'} disabled={processing} onClick={submit}>
                        {approving ? 'Setujui' : 'Tolak'}
                    </Button>
                </div>
            </div>
        </Dialog>
    );
}

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
            headerClassName: 'w-44',
            cell: (row) =>
                row.can_review ? (
                    <div className="flex gap-2">
                        <Button size="sm" onClick={() => setReview({ request: row, mode: 'approve' })}>
                            Setujui
                        </Button>
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
                            Usulan fitur tambahan atau baru per project. Yang disetujui Manager otomatis menjadi task.
                        </CardDescription>
                    </div>
                    <SegmentedTabs
                        value={filters.status ?? 'all'}
                        onChange={(value) => updateFilters({ status: value === 'all' ? null : value })}
                        tabs={[
                            { value: 'all', label: 'Semua' },
                            { value: 'pending', label: 'Menunggu', count: counts.pending },
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

            {review && <ReviewDialog request={review.request} mode={review.mode} onClose={() => setReview(null)} />}
        </AppLayout>
    );
}
