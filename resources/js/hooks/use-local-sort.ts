import { useMemo, useState } from 'react';

import type { DataTableSort } from '@/components/data-table';

type SortValue = string | number | null;

/**
 * Sorting di sisi klien untuk data yang sudah lengkap di halaman (mis. tabel KPI tim).
 * Nilai null selalu di akhir.
 */
export function useLocalSort<T, S extends string>(
    rows: T[],
    accessors: Record<S, (row: T) => SortValue>,
    initial: DataTableSort<S>,
) {
    const [sort, setSort] = useState<DataTableSort<S>>(initial);

    const sorted = useMemo(() => {
        const get = accessors[sort.key];
        const factor = sort.direction === 'asc' ? 1 : -1;

        return [...rows].sort((a, b) => {
            const left = get(a);
            const right = get(b);

            if (left === right) return 0;
            if (left === null) return 1;
            if (right === null) return -1;

            return (left < right ? -1 : 1) * factor;
        });
    }, [rows, accessors, sort]);

    return { rows: sorted, sort, setSort };
}
