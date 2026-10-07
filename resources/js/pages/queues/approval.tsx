import { QueueTable } from '@/components/items/queue-table';
import AppLayout from '@/layouts/app-layout';
import type { QueuePageProps } from '@/types/item';

export default function ApprovalQueue(props: QueuePageProps) {
    return (
        <AppLayout title="Approval">
            <QueueTable
                title="Antrian Approval"
                description="Item lulus QA yang menunggu persetujuan Anda"
                emptyMessage="Tidak ada item yang menunggu approval."
                {...props}
            />
        </AppLayout>
    );
}
