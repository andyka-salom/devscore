import type {
    EnumOption,
    ItemStatus,
    ItemType,
    Paginated,
    Priority,
    SelectOption,
    SortDirection,
    UserSummary,
} from '@/types';

export interface ProjectRef {
    code: string;
    name: string;
}

/** App\Http\Resources\QueueItemResource — baris tabel item. */
export interface ItemRow {
    id: number;
    code: string;
    title: string;
    type: EnumOption<ItemType>;
    priority: EnumOption<Priority>;
    status: EnumOption<ItemStatus>;
    difficulty: number | null;
    estimate_days: number | null;
    project: ProjectRef;
    assignee: string | null;
    qa: string | null;
    queued_at: string | null;
    updated_at: string | null;
    created_at?: string | null;
    due_date?: string | null;
    menu?: string | null;
    category?: string | null;
    is_production?: boolean;
    screenshot_path?: string | null;
    url: string;
}

export type QueueSortKey = 'queued_at' | 'code' | 'title' | 'estimate';

export interface QueueFilters {
    type: ItemType | null;
    sort: QueueSortKey;
    direction: SortDirection;
}

export type QueueCounts = Record<'all' | ItemType, number>;

/** Props halaman queues/approval & queues/qa (QueueController). */
export interface QueuePageProps {
    items: Paginated<ItemRow>;
    counts: QueueCounts;
    filters: QueueFilters;
}

export interface AvailablePageProps {
    items: Paginated<ItemRow>;
    filters: { search: string | null };
}

export type ItemSortKey = 'updated_at' | 'code' | 'title' | 'priority' | 'estimate' | 'status';

export interface ItemListFilters {
    search: string | null;
    project: number | null;
    type: ItemType | null;
    status: ItemStatus | null;
    priority: Priority | null;
    assignee: number | null;
    mine: boolean;
    sort: ItemSortKey;
    direction: SortDirection;
}

export interface ItemIndexPageProps {
    items: Paginated<ItemRow>;
    filters: ItemListFilters;
    options: {
        projects: SelectOption<number>[];
        types: SelectOption<ItemType>[];
        statuses: SelectOption<ItemStatus>[];
        priorities: SelectOption<Priority>[];
        programmers: SelectOption<number>[];
    };
    can: { create: boolean };
}

export interface ItemFormData {
    project_id: number | '';
    type: ItemType;
    title: string;
    description: string;
    steps_to_reproduce: string;
    priority: Priority;
    due_date: string;
}

export interface ItemFormPageProps {
    item: {
        id: number;
        code: string;
        project_id: number;
        type: ItemType;
        title: string;
        description: string | null;
        steps_to_reproduce: string | null;
        priority: Priority;
        due_date: string | null;
    } | null;
    options: {
        projects: SelectOption<number>[];
        types: SelectOption<ItemType>[];
        priorities: SelectOption<Priority>[];
    };
    defaults: { project_id: number | null };
}

/** App\Http\Resources\ItemDetailResource */
export interface ItemDetail {
    id: number;
    code: string;
    title: string;
    description: string | null;
    steps_to_reproduce: string | null;
    type: EnumOption<ItemType>;
    priority: EnumOption<Priority>;
    status: EnumOption<ItemStatus>;
    difficulty: number | null;
    estimate_days: number | null;
    due_date: string | null;
    project: ProjectRef;
    assignee: string | null;
    qa: string | null;
    creator: string;
    qa_fail_count: number;
    reject_count: number;
    reopen_count: number;
    started_at: string | null;
    approved_at: string | null;
    created_at: string | null;
}

export interface AllowedTransition {
    value: ItemStatus;
    label: string;
    requires_reason: boolean;
}

/** App\Http\Resources\ItemTimeline */
export interface TimelineEntry {
    id: string;
    kind: 'status' | 'note';
    note_id: number | null;
    user: UserSummary;
    from: EnumOption<ItemStatus> | null;
    to: EnumOption<ItemStatus> | null;
    body: string | null;
    attachment_url?: string | null;
    is_system: boolean;
    can_edit: boolean;
    edited: boolean;
    at: string | null;
}

export interface TriageState {
    difficulty: number | null;
    estimate_days: number | null;
    assignee_id: number | null;
    qa_id: number | null;
    locked: boolean;
    programmers: SelectOption<number>[];
    qas: SelectOption<number>[];
}

export interface ItemShowPageProps {
    item: ItemDetail;
    timeline: TimelineEntry[];
    allowed_transitions: AllowedTransition[];
    can: {
        update: boolean;
        delete: boolean;
        triage: boolean;
        assign: boolean;
        claim: boolean;
        add_note: boolean;
    };
    triage: TriageState | null;
}

export interface DashboardStats {
    my_active: number;
    backlog: number;
    in_progress: number;
    done_this_month: number;
}
