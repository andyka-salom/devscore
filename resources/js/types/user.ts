import type { EnumOption, Paginated, Role, SelectOption } from '@/types';

/** App\Http\Resources\UserResource */
export interface UserRow {
    id: number;
    name: string;
    email: string;
    role: EnumOption<Role>;
    is_active: boolean;
    created_at: string | null;
    can_delete: boolean;
}

export interface UserIndexPageProps {
    users: Paginated<UserRow>;
    filters: { search: string | null; role: Role | null };
    options: { roles: SelectOption<Role>[] };
}

export interface UserFormData {
    name: string;
    email: string;
    role: Role;
    is_active: boolean;
    password: string;
    password_confirmation: string;
}

export interface UserFormPageProps {
    user: {
        id: number;
        name: string;
        email: string;
        role: Role;
        is_active: boolean;
        is_self: boolean;
    } | null;
    options: { roles: SelectOption<Role>[] };
}
