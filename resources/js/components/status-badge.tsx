import { Badge, type BadgeTone } from '@/components/ui/badge';
import type { EnumOption, ItemStatus } from '@/types';

const STATUS_TONE: Record<ItemStatus, BadgeTone> = {
    backlog: 'neutral',
    assigned: 'info',
    in_progress: 'progress',
    on_hold: 'muted',
    ready_for_qa: 'warning',
    qa_failed: 'danger',
    qa_passed: 'warning',
    rejected: 'danger',
    done: 'success',
    cancelled: 'muted',
};

export function StatusBadge({ status, className }: { status: EnumOption<ItemStatus>; className?: string }) {
    return (
        <Badge tone={STATUS_TONE[status.value]} className={className}>
            {status.label}
        </Badge>
    );
}
