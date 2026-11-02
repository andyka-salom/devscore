import { Link, router } from '@inertiajs/react';
import { withBack } from '@/lib/back-url';
import { Filter, Plus } from 'lucide-react';
import { useState } from 'react';

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
    projectProgrammerColumn,
} from '@/components/items/item-columns';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog } from '@/components/ui/dialog';
import { Select } from '@/components/ui/select';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import type { ItemIndexPageProps, ItemRow, ItemSortKey } from '@/types/item';

const taskColumns: DataTableColumn<ItemRow, ItemSortKey>[] = [
    codeColumn((item) => item.updated_at, 'code', 'Kode & Diperbarui'),
    titleColumn('title'),
    projectProgrammerColumn(),
    priorityColumn('priority'),
    estimateColumn('estimate'),
    statusColumn('status'),
    openColumn(),
];

const bugColumns: DataTableColumn<ItemRow, ItemSortKey>[] = [
    menuColumn(),
    categoryColumn(),
    bugTitleColumn('title'),
    projectProgrammerColumn(),
    isProductionColumn(),
    priorityColumn('priority'),
    statusColumn('status'),
    createdAtColumn('updated_at'), // using updated_at sort key for now as requested
    dueDateColumn(),
    screenshotColumn(),
    openColumn(),
];

export default function ItemIndex({ items, filters, options, can }: ItemIndexPageProps) {
    // Setiap perubahan filter langsung diterapkan (pencarian dengan debounce).
    const updateFilters = useQueryFilters(filters);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const extraCount = [filters.project, filters.status, filters.assignee].filter((value) => value !== null).length;

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
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 mb-4">
                        <SearchInput
                            className="lg:col-span-2"
                            value={filters.search}
                            onSearch={(search) => updateFilters({ search })}
                            placeholder="Cari kode (ERP-42) atau judul"
                        />
                        <Select
                            aria-label="Prioritas"
                            options={options.priorities}
                            value={filters.priority}
                            onValueChange={(priority) => updateFilters({ priority })}
                            placeholder="Semua prioritas"
                        />
                        <Checkbox
                            className="self-center"
                            checked={filters.mine}
                            onCheckedChange={(mine) => updateFilters({ mine })}
                            label="Hanya item saya"
                        />
                        <Button variant="outline" onClick={() => setIsFilterOpen(true)}>
                            <Filter className="mr-2 size-4" />
                            Filter Tambahan{extraCount > 0 ? ` (${extraCount})` : ''}
                        </Button>
                    </div>

                    <Dialog open={isFilterOpen} onClose={() => setIsFilterOpen(false)} title="Filter Tambahan">
                        <div className="space-y-4">
                            <div>
                                <label className="text-sm font-medium mb-1.5 block">Project</label>
                                <Select
                                    aria-label="Project"
                                    options={options.projects}
                                    value={filters.project}
                                    onValueChange={(project) => updateFilters({ project })}
                                    placeholder="Semua project"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium mb-1.5 block">Status</label>
                                <Select
                                    aria-label="Status"
                                    options={options.statuses}
                                    value={filters.status}
                                    onValueChange={(status) => updateFilters({ status })}
                                    placeholder="Semua status"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium mb-1.5 block">Programmer</label>
                                <Select
                                    aria-label="Programmer"
                                    options={options.programmers}
                                    value={filters.assignee}
                                    onValueChange={(assignee) => updateFilters({ assignee })}
                                    placeholder="Semua programmer"
                                />
                            </div>
                            <Button className="w-full mt-4" variant="outline" onClick={() => setIsFilterOpen(false)}>
                                Tutup
                            </Button>
                        </div>
                    </Dialog>

                    <div className="flex space-x-2 border-b border-slate-200 mb-4">
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === null ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: null })}
                        >
                            Semua Item
                        </button>
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === 'task' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: 'task' })}
                        >
                            Task List
                        </button>
                        <button 
                            className={`pb-2 px-4 text-sm font-medium border-b-2 transition-colors ${filters.type === 'bug' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'}`}
                            onClick={() => updateFilters({ type: 'bug' })}
                        >
                            Bug List
                        </button>
                    </div>

                    <DataTable
                        columns={filters.type === 'bug' ? bugColumns : taskColumns}
                        rows={items.data}
                        getRowKey={(item) => item.id}
                        numberFrom={items.meta.from}
                        sort={{ key: filters.sort, direction: filters.direction }}
                        onSortChange={(sort) => updateFilters({ sort: sort.key, direction: sort.direction })}
                        onRowClick={(item) => router.visit(withBack(item.url))}
                        emptyMessage="Tidak ada item yang cocok dengan filter."
                    />
                    <Pagination paginator={items} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
