import type { EnumOption, Paginated, Priority, SelectOption } from '@/types';

export type FeatureRequestKind = 'enhancement' | 'new_feature';
export type FeatureRequestStatus = 'pending' | 'approved' | 'rejected';

/** App\Http\Resources\FeatureRequestResource */
export interface FeatureRequestRow {
    id: number;
    title: string;
    description: string | null;
    kind: EnumOption<FeatureRequestKind>;
    priority: EnumOption<Priority>;
    status: EnumOption<FeatureRequestStatus>;
    project: { code: string; name: string };
    requester: string;
    reviewer: string | null;
    review_note: string | null;
    reviewed_at: string | null;
    created_at: string | null;
    item: { code: string; url: string } | null;
    can_review: boolean;
}

export interface FeatureRequestIndexPageProps {
    requests: Paginated<FeatureRequestRow>;
    filters: { status: FeatureRequestStatus | null };
    counts: Record<'all' | FeatureRequestStatus, number>;
    can: { create: boolean };
}

export interface FeatureRequestFormData {
    project_id: number | '';
    kind: FeatureRequestKind;
    title: string;
    description: string;
    priority: Priority;
}

export interface FeatureRequestFormPageProps {
    options: {
        projects: SelectOption<number>[];
        kinds: SelectOption<FeatureRequestKind>[];
        priorities: SelectOption<Priority>[];
    };
    defaults: { project_id: number | null };
}
