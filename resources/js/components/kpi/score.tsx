import { Badge, type BadgeTone } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { EnumOption } from '@/types';
import type { KpiGrade } from '@/types/kpi';

const GRADE_TONE: Record<KpiGrade, BadgeTone> = {
    excellent: 'success',
    good: 'info',
    fair: 'warning',
    needs_improvement: 'danger',
};

export function GradeBadge({ grade }: { grade: EnumOption<KpiGrade> | null }) {
    if (!grade) return <span className="text-muted-foreground text-sm">—</span>;

    return <Badge tone={GRADE_TONE[grade.value]}>{grade.label}</Badge>;
}

/** Angka persen KPI; null ditampilkan "—" (mis. tidak ada item selesai). */
export function ScoreValue({
    value,
    className,
    suffix = '',
}: {
    value: number | null;
    className?: string;
    suffix?: string;
}) {
    if (value === null) return <span className={cn('text-muted-foreground', className)}>—</span>;

    return (
        <span className={cn('tabular-nums', className)}>
            {value.toLocaleString('id-ID', { maximumFractionDigits: 1 })}
            {suffix}
        </span>
    );
}

export function scoreTone(value: number | null): 'success' | 'warning' | 'danger' | 'default' {
    if (value === null) return 'default';
    if (value >= 90) return 'success';
    if (value >= 60) return 'warning';

    return 'danger';
}
