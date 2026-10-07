import type { EnumOption, ItemStatus, SelectOption, UserSummary } from '@/types';

export type ProjectStatus = 'planning' | 'active' | 'on_hold' | 'completed' | 'archived';

/** App\Http\Resources\ProjectResource */
export interface ProjectSummary {
    id: number;
    code: string;
    name: string;
    status: EnumOption<ProjectStatus>;
    start_date: string | null;
    end_date: string | null;
    members_count: number;
    items_count: number;
    open_items_count: number;
    url: string;
}

export interface ProjectLink {
    id?: number;
    label: string;
    url: string;
}

/** App\Http\Resources\NoteResource */
export interface Note {
    id: number;
    body: string;
    is_system: boolean;
    user: UserSummary;
    created_at: string | null;
    edited: boolean;
    can_edit: boolean;
}

export interface ProjectShowPageProps {
    project: {
        id: number;
        code: string;
        name: string;
        description: string | null;
        status: EnumOption<ProjectStatus>;
        start_date: string | null;
        end_date: string | null;
        members: UserSummary[];
        links: ProjectLink[];
    };
    summary: {
        status_counts: { status: EnumOption<ItemStatus>; count: number }[];
        total_points: number;
        done_points: number;
        progress: number;
    };
    notes: Note[];
    can: { update: boolean; add_note: boolean; create_item: boolean };
}

export interface ProjectFormData {
    code: string;
    name: string;
    description: string;
    status: ProjectStatus;
    start_date: string;
    end_date: string;
    member_ids: number[];
    links: ProjectLink[];
}

export interface ProjectFormPageProps {
    project:
        | (Omit<ProjectFormData, 'description' | 'start_date' | 'end_date'> & {
              id: number;
              description: string | null;
              start_date: string | null;
              end_date: string | null;
          })
        | null;
    options: {
        statuses: SelectOption<ProjectStatus>[];
        programmers: SelectOption<number>[];
        qas: SelectOption<number>[];
    };
}
