import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { DataTable, type DataTableColumn } from '@/components/data-table';
import {
    codeColumn,
    estimateColumn,
    openColumn,
    priorityColumn,
    statusColumn,
    titleColumn,
    menuColumn,
    categoryColumn,
    isProductionColumn,
    bugTitleColumn,
    createdAtColumn,
    dueDateColumn,
    screenshotColumn,
} from '@/components/items/item-columns';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import type { ItemIndexPageProps, ItemRow, ItemSortKey } from '@/types/item';

const taskColumns: DataTableColumn<ItemRow, ItemSortKey>[] = [
    codeColumn((item) => item.updated_at, 'code', 'Kode & Diperbarui'),
    titleColumn('title'),
    priorityColumn('priority'),
    estimateColumn('estimate'),
    statusColumn('status'),
    openColumn(),
];

const bugColumns: DataTableColumn<ItemRow, ItemSortKey>[] = [
    menuColumn(),
    categoryColumn(),
    bugTitleColumn('title'),
    isProductionColumn(),
    priorityColumn('priority'),
    statusColumn('status'),
    createdAtColumn('updated_at'), // using updated_at sort key for now as requested
    dueDateColumn(),
    screenshotColumn(),
    openColumn(),
];

export default function ItemIndex({ items, filters, options, can }: ItemIndexPageProps) {
    const updateFilters = useQueryFilters(filters);

    return (
        <AppLayout title="Item">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Daftar Item</CardTitle>
                        <CardDescription>Seluruh bug & task yang dapat Anda akses</CardDescription>
                    </div>
                    {can.create && (
                        <Link href="/items/create" className={buttonVariants()}>
                            <Plus /> Buat Item
                        </Link>
                    )}
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                        <SearchInput
                            className="lg:col-span-2"
                            value={filters.search}
                            onSearch={(search) => updateFilters({ search })}
                            placeholder="Cari kode (ERP-42) atau judul"
                        />
                        <Select
                            aria-label="Project"
                            options={options.projects}
                            value={filters.project}
                            onValueChange={(project) => updateFilters({ project })}
                            placeholder="Semua project"
                        />
                        <Select
                            aria-label="Status"
                            options={options.statuses}
                            value={filters.status}
                            onValueChange={(status) => updateFilters({ status })}
                            placeholder="Semua status"
                        />
                        <Select
                            aria-label="Tipe"
                            options={[
                                { value: 'all', label: 'Semua Tipe' },
                                ...options.types,
                            ]}
                            value={filters.type ?? 'all'}
                            onValueChange={(type) => updateFilters({ type: type === 'all' ? null : type as any })}
                            placeholder="Semua tipe"
                        />
                        <Select
                            aria-label="Prioritas"
                            options={options.priorities}
                            value={filters.priority}
                            onValueChange={(priority) => updateFilters({ priority })}
                            placeholder="Semua prioritas"
                        />
                        <Select
                            aria-label="Programmer"
                            className="lg:col-span-2"
                            options={options.programmers}
                            value={filters.assignee}
                            onValueChange={(assignee) => updateFilters({ assignee })}
                            placeholder="Semua programmer"
                        />
                        <Checkbox
                            className="self-center"
                            checked={filters.mine}
                            onCheckedChange={(mine) => updateFilters({ mine })}
                            label="Hanya item saya"
                        />
                    </div>

                    <div className="flex space-x-2 border-b border-slate-200 mb-4">
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === null ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: null })}
                        >
                            Semua Item
                        </button>
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === 'task' as any ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: 'task' as any })}
                        >
                            Task List
                        </button>
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === 'bug' as any ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: 'bug' as any })}
                        >
                            Bug List
                        </button>
                    </div>

                    <DataTable
                        columns={filters.type === 'bug' as any ? bugColumns : taskColumns}
                        rows={items.data}
                        getRowKey={(item) => item.id}
                        numberFrom={items.meta.from}
                        sort={{ key: filters.sort, direction: filters.direction }}
                        onSortChange={(sort) => updateFilters({ sort: sort.key, direction: sort.direction })}
                        onRowClick={(item) => router.visit(item.url)}
                        emptyMessage="Tidak ada item yang cocok dengan filter."
                    />
                    <Pagination paginator={items} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
