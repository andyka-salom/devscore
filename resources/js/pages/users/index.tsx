import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type DataTableColumn } from '@/components/data-table';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import { useQueryFilters } from '@/hooks/use-query-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import type { UserIndexPageProps, UserRow } from '@/types/user';

export default function UserIndex({ users, filters, options }: UserIndexPageProps) {
    const updateFilters = useQueryFilters(filters);

    const destroy = (user: UserRow) => router.delete(`/users/${user.id}`, { preserveScroll: true });

    const columns: DataTableColumn<UserRow>[] = [
        {
            id: 'name',
            header: 'User',
            cell: (row) => (
                <div className="leading-tight">
                    <p className="font-medium">{row.name}</p>
                    <p className="text-muted-foreground mt-0.5 text-xs">{row.email}</p>
                </div>
            ),
        },
        {
            id: 'role',
            header: 'Role',
            headerClassName: 'w-36',
            cell: (row) => <Badge tone="info">{row.role.label}</Badge>,
        },
        {
            id: 'status',
            header: 'Status',
            headerClassName: 'w-32',
            cell: (row) => (
                <Badge tone={row.is_active ? 'success' : 'muted'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            ),
        },
        {
            id: 'created_at',
            header: 'Dibuat',
            headerClassName: 'w-36',
            cell: (row) => <span className="text-muted-foreground text-xs">{formatDate(row.created_at)}</span>,
        },
        {
            id: 'actions',
            header: <span className="sr-only">Aksi</span>,
            headerClassName: 'w-28',
            cell: (row) => (
                <div className="flex justify-end gap-1">
                    <Link
                        href={`/users/${row.id}/edit`}
                        className={buttonVariants({ variant: 'ghost', size: 'icon' })}
                        aria-label={`Edit ${row.name}`}
                    >
                        <Pencil />
                    </Link>
                    {row.can_delete && (
                        <ConfirmDialog
                            title="Hapus user"
                            description={`${row.name} akan dihapus permanen. User yang sudah punya riwayat kerja tidak bisa dihapus — nonaktifkan saja.`}
                            confirmLabel="Hapus"
                            variant="destructive"
                            onConfirm={() => destroy(row)}
                            trigger={(open) => (
                                <Button variant="ghost" size="icon" aria-label={`Hapus ${row.name}`} onClick={open}>
                                    <Trash2 className="text-destructive" />
                                </Button>
                            )}
                        />
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            title="Manajemen User"
            actions={
                <Link href="/users/create" className={buttonVariants()}>
                    <Plus /> User Baru
                </Link>
            }
        >
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle>Manajemen User</CardTitle>
                        <CardDescription>
                            Tambah, ubah, nonaktifkan, atau hapus akun. User nonaktif tidak bisa login.
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <SearchInput
                            value={filters.search}
                            onSearch={(search) => updateFilters({ search })}
                            placeholder="Cari nama atau email…"
                            className="w-64"
                        />
                        <Select
                            aria-label="Filter role"
                            className="w-44"
                            options={options.roles}
                            value={filters.role ?? ''}
                            placeholder="Semua role"
                            onValueChange={(role) => updateFilters({ role })}
                        />
                    </div>
                </CardHeader>
                <CardContent>
                    <DataTable
                        columns={columns}
                        rows={users.data}
                        getRowKey={(row) => row.id}
                        numberFrom={users.meta.from}
                        emptyMessage="Tidak ada user."
                    />
                    <Pagination paginator={users} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
