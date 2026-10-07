import { Database, Lock } from 'lucide-react';

import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { KpiSource } from '@/types/kpi';

/** Penanda asal angka KPI: snapshot periode tertutup atau perhitungan langsung. */
export function KpiSourceNote({ source }: { source: KpiSource }) {
    const snapshot = source.type === 'snapshot';
    const Icon = snapshot ? Lock : Database;

    return (
        <p
            className={cn(
                'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs',
                snapshot ? 'bg-emerald-50 text-emerald-800' : 'bg-muted text-muted-foreground',
            )}
        >
            <Icon className="size-3.5" />
            {source.label}
            {snapshot && source.closed_at && <span>· ditutup {formatDateTime(source.closed_at)}</span>}
        </p>
    );
}
