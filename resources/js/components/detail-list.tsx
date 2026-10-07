import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export interface DetailEntry {
    label: string;
    value: ReactNode;
}

/** Daftar label–nilai dua kolom untuk panel informasi. */
export function DetailList({ entries, className }: { entries: DetailEntry[]; className?: string }) {
    return (
        <dl className={cn('grid gap-x-6 gap-y-4 sm:grid-cols-2', className)}>
            {entries.map((entry) => (
                <div key={entry.label}>
                    <dt className="text-muted-foreground text-xs">{entry.label}</dt>
                    <dd className="mt-0.5 text-sm font-medium">{entry.value ?? '—'}</dd>
                </div>
            ))}
        </dl>
    );
}
