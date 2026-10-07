import { cn } from '@/lib/utils';

export interface SegmentedTab<T extends string> {
    value: T;
    label: string;
    count?: number;
}

interface SegmentedTabsProps<T extends string> {
    tabs: SegmentedTab<T>[];
    value: T;
    onChange: (value: T) => void;
    className?: string;
}

/**
 * Tab bergaya segmented control; tab aktif menampilkan pill jumlah.
 */
export function SegmentedTabs<T extends string>({ tabs, value, onChange, className }: SegmentedTabsProps<T>) {
    return (
        <div role="tablist" className={cn('inline-flex flex-wrap items-center gap-1 rounded-lg border p-1', className)}>
            {tabs.map((tab) => {
                const active = tab.value === value;

                return (
                    <button
                        key={tab.value}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        onClick={() => onChange(tab.value)}
                        className={cn(
                            'inline-flex h-8 items-center gap-2 rounded-md px-3 text-[13px] transition-colors',
                            active
                                ? 'bg-card text-foreground border font-medium shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {tab.label}
                        {tab.count !== undefined && tab.count > 0 && (
                            <span
                                className={cn(
                                    'inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-5 font-semibold',
                                    active ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                                )}
                            >
                                {tab.count > 99 ? '99+' : tab.count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
