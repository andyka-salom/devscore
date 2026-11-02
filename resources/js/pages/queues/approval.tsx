import { router } from '@inertiajs/react';
import { CheckCheck } from 'lucide-react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { QueueTable } from '@/components/items/queue-table';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { ApprovalPageProps } from '@/types/item';

export default function ApprovalQueue({ can, ...props }: ApprovalPageProps) {
    const [processing, setProcessing] = useState(false);
    // Total sesuai filter aktif (project & tipe), bukan hanya halaman ini.
    const total = props.items.meta.total;

    const approveAll = () =>
        router.post(
            '/approval/approve-all',
            { type: props.filters.type, project: props.filters.project ?? null },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );

    const actions =
        can.approve_all && total > 0 ? (
            <ConfirmDialog
                title={`Setujui ${total} item?`}
                description="Semua item lulus QA sesuai filter project & tipe yang aktif akan ditandai Selesai."
                confirmLabel="Setujui Semua"
                processing={processing}
                onConfirm={approveAll}
                trigger={(open) => (
                    <Button size="sm" onClick={open} disabled={processing}>
                        <CheckCheck className="mr-1.5 size-4" /> Setujui Semua ({total})
                    </Button>
                )}
            />
        ) : undefined;

    return (
        <AppLayout title="Approval" actions={actions}>
            <QueueTable
                title="Antrian Approval"
                description="Item lulus QA yang menunggu persetujuan Anda"
                emptyMessage="Tidak ada item yang menunggu approval."
                {...props}
            />
        </AppLayout>
    );
}
