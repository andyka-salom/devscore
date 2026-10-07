import { router } from '@inertiajs/react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { codeColumn, estimateColumn, openColumn, statusColumn, titleColumn } from '@/components/items/item-columns';
import { Pagination } from '@/components/pagination';
import { SegmentedTabs, type SegmentedTab } from '@/components/segmented-tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useQueryFilters } from '@/hooks/use-query-filters';
import type { ItemType } from '@/types';
import type { ItemRow, QueuePageProps, QueueSortKey } from '@/types/item';

type TypeTab = 'all' | ItemType;

const columns: DataTableColumn<ItemRow, QueueSortKey>[] = [
    codeColumn((item) => item.queued_at, 'queued_at'),
    titleColumn('title'),
    estimateColumn('estimate'),
    statusColumn(),
    openColumn(),
];

interface QueueTableProps extends QueuePageProps {
    title: string;
    description: string;
    emptyMessage: string;
}

/**
 * Kartu antrian item (tab tipe + tabel + paginasi). Dipakai antrian Approval & QA.
 */
export function QueueTable({ title, description, emptyMessage, items, counts, filters }: QueueTableProps) {
    const updateFilters = useQueryFilters(filters);

    const tabs: SegmentedTab<TypeTab>[] = [
        { value: 'all', label: 'Semua', count: counts.all },
        { value: 'bug', label: 'Bug', count: counts.bug },
        { value: 'task', label: 'Task', count: counts.task },
    ];

    return (
        <Card>
            <CardHeader>
                <div>
                    <CardTitle>{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                <SegmentedTabs
                    tabs={tabs}
                    value={filters.type ?? 'all'}
                    onChange={(value) => updateFilters({ type: value === 'all' ? null : value })}
                />
            </CardHeader>
            <CardContent>
                <DataTable
                    columns={columns}
                    rows={items.data}
                    getRowKey={(item) => item.id}
                    numberFrom={items.meta.from}
                    sort={{ key: filters.sort, direction: filters.direction }}
                    onSortChange={(sort) => updateFilters({ sort: sort.key, direction: sort.direction })}
                    onRowClick={(item) => router.visit(item.url)}
                    emptyMessage={emptyMessage}
                />
                <Pagination paginator={items} />
            </CardContent>
        </Card>
    );
}
