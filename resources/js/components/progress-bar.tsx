import { cn } from '@/lib/utils';

interface ProgressBarProps {
    /** 0–100; nilai di atas 100 dipotong. */
    value: number | null;
    className?: string;
    tone?: 'default' | 'success' | 'warning' | 'danger';
    label?: string;
}

const TONE = {
    default: 'bg-primary',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
};

export function ProgressBar({ value, className, tone = 'default', label }: ProgressBarProps) {
    const width = value === null ? 0 : Math.max(0, Math.min(100, value));

    return (
        <div
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={value ?? undefined}
            aria-label={label}
            className={cn('bg-muted h-2 w-full overflow-hidden rounded-full', className)}
        >
            <div className={cn('h-full rounded-full transition-all', TONE[tone])} style={{ width: `${width}%` }} />
        </div>
    );
}
