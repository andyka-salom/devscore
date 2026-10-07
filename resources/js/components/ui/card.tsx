import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Card({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('bg-card text-card-foreground rounded-lg border shadow-xs', className)} {...props} />;
}

export function CardHeader({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            className={cn('flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between', className)}
            {...props}
        />
    );
}

export function CardTitle({ className, ...props }: ComponentProps<'h2'>) {
    return <h2 className={cn('text-lg leading-tight font-semibold', className)} {...props} />;
}

export function CardDescription({ className, ...props }: ComponentProps<'p'>) {
    return <p className={cn('text-muted-foreground mt-1 text-sm', className)} {...props} />;
}

export function CardContent({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('px-4 pb-4', className)} {...props} />;
}
