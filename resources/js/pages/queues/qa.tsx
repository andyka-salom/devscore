import { QueueTable } from '@/components/items/queue-table';
import AppLayout from '@/layouts/app-layout';
import type { QueuePageProps } from '@/types/item';

export default function QaQueue(props: QueuePageProps) {
    return (
        <AppLayout title="Antrian QA">
            <QueueTable
                title="Antrian QA"
                description="Item siap diuji yang menunggu keputusan QA"
                emptyMessage="Tidak ada item di antrian QA."
                {...props}
            />
        </AppLayout>
    );
}
