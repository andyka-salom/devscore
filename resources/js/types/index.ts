/** Bentuk standar enum dari backend (App\Http\Resources\EnumOption). */
export interface EnumOption<T extends string = string> {
    value: T;
    label: string;
}

export type Role = 'programmer' | 'qa' | 'manager' | 'admin';

export type ItemStatus =
    | 'backlog'
    | 'assigned'
    | 'in_progress'
    | 'on_hold'
    | 'ready_for_qa'
    | 'qa_failed'
    | 'qa_passed'
    | 'rejected'
    | 'done'
    | 'cancelled';

export type ItemType = 'bug' | 'task';

export type Priority = 'low' | 'medium' | 'high' | 'critical';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: EnumOption<Role>;
}

export interface UserSummary {
    id: number;
    name: string;
    role: EnumOption<Role>;
}

/** Akses menu dari backend: angka = badge, null/false = tidak berwenang. */
export interface NavAccess {
    approval: number | null;
    qa: number | null;
    available: number | null;
    kpi: boolean;
    settings: boolean;
}

export interface SharedData {
    name: string;
    auth: { user: AuthUser | null };
    nav: NavAccess | null;
    [key: string]: unknown;
}

export interface SelectOption<T extends string | number = string> {
    value: T;
    label: string;
}

export interface FlashData {
    success?: string;
    error?: string;
}

/** Hasil `JsonResource::collection($paginator)` Laravel. */
export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export type SortDirection = 'asc' | 'desc';
