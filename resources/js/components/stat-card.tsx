import type { LucideIcon } from 'lucide-react';

import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface StatCardProps {
    label: string;
    value: number | string | null;
    icon: LucideIcon;
    hint?: string;
    className?: string;
}

/** Kartu angka ringkas. `value` null = sedang dimuat. */
export function StatCard({ label, value, icon: Icon, hint, className }: StatCardProps) {
    return (
        <Card className={cn('flex items-start justify-between gap-4 p-5', className)}>
            <div>
                <p className="text-muted-foreground text-sm">{label}</p>
                {value === null ? (
                    <div className="bg-muted mt-2 h-8 w-16 animate-pulse rounded" />
                ) : (
                    <p className="mt-1 text-3xl font-semibold tabular-nums">{value}</p>
                )}
                {hint && <p className="text-muted-foreground mt-1 text-xs">{hint}</p>}
            </div>
            <span className="bg-muted text-muted-foreground rounded-lg p-2.5">
                <Icon className="size-5" />
            </span>
        </Card>
    );
}
