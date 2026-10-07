import { cva, type VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export const badgeVariants = cva(
    'inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold tracking-wide whitespace-nowrap uppercase',
    {
        variants: {
            tone: {
                neutral: 'bg-zinc-100 text-zinc-600',
                info: 'bg-sky-50 text-sky-700',
                progress: 'bg-indigo-50 text-indigo-700',
                warning: 'bg-amber-50 text-amber-600',
                success: 'bg-emerald-50 text-emerald-700',
                danger: 'bg-red-50 text-red-600',
                muted: 'bg-zinc-50 text-zinc-400',
            },
        },
        defaultVariants: { tone: 'neutral' },
    },
);

export type BadgeTone = NonNullable<VariantProps<typeof badgeVariants>['tone']>;

export type BadgeProps = ComponentProps<'span'> & VariantProps<typeof badgeVariants>;

export function Badge({ className, tone, ...props }: BadgeProps) {
    return <span className={cn(badgeVariants({ tone }), className)} {...props} />;
}
