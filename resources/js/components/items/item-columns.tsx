import { Link } from '@inertiajs/react';
import { ChevronRight, CheckCircle2, Image as ImageIcon } from 'lucide-react';

import type { DataTableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/status-badge';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { formatDate, formatDays } from '@/lib/format';
import type { Priority } from '@/types';
import type { ItemRow } from '@/types/item';

/**
 * Pabrik kolom tabel item. Setiap halaman memilih kolom & kunci sort-nya sendiri.
 */

const PRIORITY_TONE: Record<Priority, BadgeTone> = {
    low: 'neutral',
    medium: 'info',
    high: 'warning',
    critical: 'danger',
};

export function codeColumn<S extends string>(
    dateOf: (item: ItemRow) => string | null,
    sortKey?: S,
    header = 'Kode & Tanggal',
): DataTableColumn<ItemRow, S> {
    return {
        id: 'code',
        header,
        sortKey,
        headerClassName: 'w-40',
        cell: (item) => (
            <div className="leading-tight">
                <p className="text-[13px] font-medium">{item.code}</p>
                <p className="text-muted-foreground mt-0.5 text-[13px]">{formatDate(dateOf(item))}</p>
            </div>
        ),
    };
}

export function titleColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'title',
        header: 'Item / Project',
        sortKey,
        cell: (item) => (
            <div className="leading-tight">
                <p className="line-clamp-1 font-medium">{item.title}</p>
                <p className="text-muted-foreground mt-0.5 text-xs">
                    {item.type.label} · {item.project.name} · Programmer: {item.assignee ?? '—'}
                </p>
            </div>
        ),
    };
}

export function estimateColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'estimate',
        header: 'Estimasi',
        sortKey,
        headerClassName: 'w-36',
        cell: (item) => (
            <div className="leading-tight">
                <p className="font-semibold">{formatDays(item.estimate_days)}</p>
                <p className="text-muted-foreground mt-0.5 text-xs">Difficulty {item.difficulty ?? '—'}</p>
            </div>
        ),
    };
}

export function priorityColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'priority',
        header: 'Prioritas',
        sortKey,
        headerClassName: 'w-28',
        cell: (item) => <Badge tone={PRIORITY_TONE[item.priority.value]}>{item.priority.label}</Badge>,
    };
}

export function statusColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'status',
        header: 'Status',
        sortKey,
        headerClassName: 'w-32',
        cell: (item) => <StatusBadge status={item.status} />,
    };
}

export function openColumn<S extends string>(): DataTableColumn<ItemRow, S> {
    return {
        id: 'action',
        header: <span className="sr-only">Aksi</span>,
        headerClassName: 'w-12',
        className: 'text-right',
        cell: (item) => (
            <Link
                href={item.url}
                onClick={(event) => event.stopPropagation()}
                aria-label={`Buka ${item.code}`}
                className="bg-card text-muted-foreground hover:bg-accent hover:text-foreground inline-flex size-7 items-center justify-center rounded-md border"
            >
                <ChevronRight className="size-4" />
            </Link>
        ),
    };
}

export function menuColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'menu',
        header: 'Menu',
        sortKey,
        headerClassName: 'w-32',
        cell: (item) => <span className="text-sm font-medium">{item.menu ?? '—'}</span>,
    };
}

export function categoryColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'category',
        header: 'Category',
        sortKey,
        headerClassName: 'w-32',
        cell: (item) => <span className="text-sm">{item.category ?? '—'}</span>,
    };
}

export function isProductionColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'is_production',
        header: 'Prod.',
        sortKey,
        headerClassName: 'w-20',
        cell: (item) => (
            item.is_production ? <CheckCircle2 className="size-4 text-green-500" /> : <span className="text-muted-foreground">—</span>
        ),
    };
}

export function bugTitleColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'title',
        header: 'Issue',
        sortKey,
        cell: (item) => (
            <div className="leading-tight">
                <p className="line-clamp-1 font-medium">{item.title}</p>
                <p className="text-muted-foreground mt-0.5 text-xs">{item.code}</p>
            </div>
        ),
    };
}

export function createdAtColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'created_at',
        header: 'Tanggal Pengajuan',
        sortKey,
        headerClassName: 'w-40',
        cell: (item) => <span className="text-sm">{formatDate(item.created_at)}</span>,
    };
}

export function dueDateColumn<S extends string>(sortKey?: S): DataTableColumn<ItemRow, S> {
    return {
        id: 'due_date',
        header: 'Target Perbaikan',
        sortKey,
        headerClassName: 'w-40',
        cell: (item) => <span className="text-sm">{formatDate(item.due_date)}</span>,
    };
}

export function screenshotColumn<S extends string>(): DataTableColumn<ItemRow, S> {
    return {
        id: 'screenshot',
        header: 'Screenshot',
        headerClassName: 'w-24',
        cell: (item) => (
            item.screenshot_path ? (
                <a href={`/storage/${item.screenshot_path}`} target="_blank" rel="noreferrer" className="text-blue-500 hover:text-blue-600">
                    <ImageIcon className="size-4" />
                </a>
            ) : <span className="text-muted-foreground">—</span>
        ),
    };
}
