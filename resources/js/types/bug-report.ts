import type { EnumOption, Paginated, Priority, SelectOption } from '@/types';

export type BugReportStatus = 'pending' | 'awaiting_chief' | 'approved' | 'rejected';

/** App\Http\Resources\BugReportResource */
export interface BugReportRow {
    id: number;
    title: string;
    description: string | null;
    steps_to_reproduce: string | null;
    priority: EnumOption<Priority>;
    status: EnumOption<BugReportStatus>;
    project: { code: string; name: string };
    reporter: string;
    reviewer: string | null;
    review_note: string | null;
    reviewed_at: string | null;
    created_at: string | null;
    photos: string[];
    item: { code: string; url: string } | null;
    escalated_by: string | null;
    escalated_at: string | null;
    escalation_note: string | null;
    can_review: boolean;
    can_escalate: boolean;
}

export interface BugReportIndexPageProps {
    reports: Paginated<BugReportRow>;
    filters: { status: BugReportStatus | null };
    counts: Record<'all' | BugReportStatus, number>;
    can: { create: boolean };
}

export interface BugReportFormData {
    project_id: number | '';
    title: string;
    description: string;
    steps_to_reproduce: string;
    priority: Priority;
    photos: File[];
}

export interface BugReportFormPageProps {
    options: {
        projects: SelectOption<number>[];
        priorities: SelectOption<Priority>[];
    };
    defaults: { project_id: number | null };
    max_photos: number;
}
