import { router } from '@inertiajs/react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import { codeColumn, estimateColumn, openColumn, priorityColumn, titleColumn } from '@/components/items/item-columns';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import type { AvailablePageProps, ItemRow } from '@/types/item';

const columns: DataTableColumn<ItemRow>[] = [
    codeColumn((item) => item.updated_at, undefined, 'Kode & Tanggal'),
    titleColumn(),
    priorityColumn(),
    estimateColumn(),
    {
        id: 'qa',
        header: 'QA',
        headerClassName: 'w-40',
        cell: (item) => <span className="text-sm">{item.qa ?? '—'}</span>,
    },
    openColumn(),
];

export default function AvailableItems({ items, filters }: AvailablePageProps) {
    const updateFilters = useQueryFilters(filters);

    return (
        <AppLayout title="Task Tersedia">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Task Tersedia</CardTitle>
                        <CardDescription>
                            Bug & task yang sudah di-triage dan siap di-claim. Buka item lalu klik “Claim”.
                        </CardDescription>
                    </div>
                    <SearchInput
                        className="sm:w-72"
                        value={filters.search}
                        onSearch={(search) => updateFilters({ search })}
                        placeholder="Cari kode atau judul"
                    />
                </CardHeader>
                <CardContent>
                    <DataTable
                        columns={columns}
                        rows={items.data}
                        getRowKey={(item) => item.id}
                        numberFrom={items.meta.from}
                        onRowClick={(item) => router.visit(item.url)}
                        emptyMessage="Belum ada task yang bisa di-claim."
                    />
                    <Pagination paginator={items} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
