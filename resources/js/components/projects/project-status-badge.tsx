import { Badge, type BadgeTone } from '@/components/ui/badge';
import type { EnumOption } from '@/types';
import type { ProjectStatus } from '@/types/project';

const TONE: Record<ProjectStatus, BadgeTone> = {
    planning: 'info',
    active: 'success',
    on_hold: 'warning',
    completed: 'progress',
    archived: 'muted',
};

export function ProjectStatusBadge({ status }: { status: EnumOption<ProjectStatus> }) {
    return <Badge tone={TONE[status.value]}>{status.label}</Badge>;
}
