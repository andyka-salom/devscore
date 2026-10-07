import { router } from '@inertiajs/react';
import { useCallback } from 'react';

type QueryValue = string | number | boolean | null | undefined;

/**
 * Mengubah query string halaman saat ini (filter/sort) lewat Inertia visit.
 * Nilai null/undefined/''/false dihapus, true dikirim sebagai 1; `page` di-reset ke halaman pertama.
 */
export function useQueryFilters<F extends { [K in keyof F]: QueryValue }>(current: F) {
    return useCallback(
        (changes: Partial<F>) => {
            const merged: Record<string, QueryValue> = { ...current, ...changes };
            const query: Record<string, string | number> = {};

            for (const [key, value] of Object.entries(merged)) {
                if (value === null || value === undefined || value === '' || value === false) continue;
                query[key] = value === true ? 1 : value;
            }

            router.get(window.location.pathname, query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        [current],
    );
}
