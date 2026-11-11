import type { EnumOption, Priority } from '@/types';
import type { BugReportRow } from '@/types/bug-report';
import type { FeatureRequestRow } from '@/types/feature-request';

/** Baris gabungan di halaman Need Approval (fitur + bug), dibentuk di frontend dari props controller. */
export interface NeedApprovalRow {
    entity: 'feature' | 'bug';
    key: string;
    baseUrl: string;
    title: string;
    description: string | null;
    typeLabel: string;
    project: { code: string; name: string };
    author: string;
    priority: EnumOption<Priority>;
    photos: string[];
    escalated_by: string | null;
    escalated_at: string | null;
    escalation_note: string | null;
}

/** App\Http\Controllers\NeedApprovalController */
export interface NeedApprovalPageProps {
    features: FeatureRequestRow[];
    bugs: BugReportRow[];
}
