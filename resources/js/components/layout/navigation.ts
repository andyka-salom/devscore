import {
    ChartColumnBig,
    ClipboardCheck,
    FolderKanban,
    Hand,
    House,
    ListTodo,
    Settings,
    ShieldCheck,
    Bug,
    CheckSquare,
    type LucideIcon,
} from 'lucide-react';

import type { NavAccess } from '@/types';

export interface NavItem {
    title: string;
    href: string;
    icon: LucideIcon;
    /**
     * Kunci shared prop `nav`. Item disembunyikan bila nilainya null/false (tidak berwenang);
     * nilai angka ditampilkan sebagai badge.
     */
    accessKey?: keyof NavAccess;
}

export interface NavSection {
    title: string;
    items: NavItem[];
}

export const navigation: NavSection[] = [
    {
        title: 'Menu',
        items: [
            { title: 'Beranda', href: '/', icon: House },
            { title: 'Project', href: '/projects', icon: FolderKanban },
            { title: 'Semua Item', href: '/items', icon: ListTodo },
            { title: 'Task List', href: '/items?type=task', icon: CheckSquare },
            { title: 'Bug List', href: '/items?type=bug', icon: Bug },
        ],
    },
    {
        title: 'Antrian',
        items: [
            { title: 'Task Tersedia', href: '/items/available', icon: Hand, accessKey: 'available' },
            { title: 'Antrian QA', href: '/qa', icon: ClipboardCheck, accessKey: 'qa' },
            { title: 'Approval', href: '/approval', icon: ShieldCheck, accessKey: 'approval' },
        ],
    },
    {
        title: 'Kinerja',
        items: [{ title: 'KPI', href: '/kpi', icon: ChartColumnBig, accessKey: 'kpi' }],
    },
    {
        title: 'Admin',
        items: [{ title: 'Pengaturan', href: '/settings', icon: Settings, accessKey: 'settings' }],
    },
];

export function isAllowed(item: NavItem, access: NavAccess | null): boolean {
    if (!item.accessKey) return true;

    const value = access?.[item.accessKey];

    return value !== null && value !== undefined && value !== false;
}

export function badgeCount(item: NavItem, access: NavAccess | null): number | null {
    const value = item.accessKey ? access?.[item.accessKey] : null;

    return typeof value === 'number' ? value : null;
}
