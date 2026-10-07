import { ArrowDown, ArrowUp, ChevronsUpDown, Inbox } from 'lucide-react';
import type { ReactNode } from 'react';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { SortDirection } from '@/types';

export interface DataTableColumn<T, S extends string = string> {
    id: string;
    header: ReactNode;
    cell: (row: T) => ReactNode;
    /** Kunci sort yang dikirim ke backend; kolom tanpa sortKey tidak bisa di-sort. */
    sortKey?: S;
    headerClassName?: string;
    className?: string;
}

export interface DataTableSort<S extends string = string> {
    key: S;
    direction: SortDirection;
}

interface DataTableProps<T, S extends string> {
    columns: DataTableColumn<T, S>[];
    rows: T[];
    getRowKey: (row: T) => string | number;
    sort?: DataTableSort<S>;
    onSortChange?: (sort: DataTableSort<S>) => void;
    /** Tampilkan kolom "No" dengan offset (mis. `meta.from` dari paginator). */
    numberFrom?: number | null;
    emptyMessage?: string;
    onRowClick?: (row: T) => void;
}

/**
 * Tabel generik dengan sorting server-side, penomoran, dan empty state.
 */
export function DataTable<T, S extends string = string>({
    columns,
    rows,
    getRowKey,
    sort,
    onSortChange,
    numberFrom,
    emptyMessage = 'Tidak ada data.',
    onRowClick,
}: DataTableProps<T, S>) {
    const numbered = numberFrom !== undefined;
    const colSpan = columns.length + (numbered ? 1 : 0);

    return (
        <Table>
            <TableHeader>
                <TableRow className="border-0 hover:bg-transparent">
                    {numbered && <TableHead className="w-14">No</TableHead>}
                    {columns.map((column) => (
                        <TableHead key={column.id} className={column.headerClassName}>
                            {column.sortKey && onSortChange
                                ? renderSortButton(column.header, column.sortKey, sort, onSortChange)
                                : column.header}
                        </TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.length === 0 ? (
                    <TableRow className="hover:bg-transparent">
                        <TableCell colSpan={colSpan} className="py-16 text-center">
                            <div className="text-muted-foreground flex flex-col items-center gap-2">
                                <Inbox className="size-8 stroke-1" />
                                <p className="text-sm">{emptyMessage}</p>
                            </div>
                        </TableCell>
                    </TableRow>
                ) : (
                    rows.map((row, index) => (
                        <TableRow
                            key={getRowKey(row)}
                            onClick={onRowClick ? () => onRowClick(row) : undefined}
                            className={cn(onRowClick && 'cursor-pointer')}
                        >
                            {numbered && <TableCell className="text-sm">{(numberFrom ?? 1) + index}</TableCell>}
                            {columns.map((column) => (
                                <TableCell key={column.id} className={column.className}>
                                    {column.cell(row)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}

function renderSortButton<S extends string>(
    label: ReactNode,
    key: S,
    sort: DataTableSort<S> | undefined,
    onSortChange: (sort: DataTableSort<S>) => void,
) {
    const direction = sort?.key === key ? sort.direction : null;

    return (
        <SortButton
            label={label}
            direction={direction}
            onClick={() => onSortChange({ key, direction: direction === 'asc' ? 'desc' : 'asc' })}
        />
    );
}

function SortButton({
    label,
    direction,
    onClick,
}: {
    label: ReactNode;
    direction: SortDirection | null;
    onClick: () => void;
}) {
    const Icon = direction === 'asc' ? ArrowUp : direction === 'desc' ? ArrowDown : ChevronsUpDown;

    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'hover:text-foreground inline-flex items-center gap-1.5',
                direction && 'text-foreground font-semibold',
            )}
        >
            {label}
            <Icon className="size-3.5" />
        </button>
    );
}
