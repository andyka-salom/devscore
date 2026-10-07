import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Input({ className, ...props }: ComponentProps<'input'>) {
    return (
        <input
            className={cn(
                'border-input bg-card placeholder:text-muted-foreground focus-visible:ring-ring aria-invalid:border-destructive flex h-10 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}

export function Textarea({ className, ...props }: ComponentProps<'textarea'>) {
    return (
        <textarea
            className={cn(
                'border-input bg-card placeholder:text-muted-foreground focus-visible:ring-ring aria-invalid:border-destructive flex min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}

export function Label({ className, ...props }: ComponentProps<'label'>) {
    return <label className={cn('text-sm leading-none font-medium', className)} {...props} />;
}

export function InputError({ message, className }: { message?: string; className?: string }) {
    return message ? <p className={cn('text-destructive text-sm', className)}>{message}</p> : null;
}
