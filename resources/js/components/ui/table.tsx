import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Table({ className, ...props }: ComponentProps<'table'>) {
    return (
        <div className="relative w-full overflow-x-auto">
            <table className={cn('w-full caption-bottom text-sm', className)} {...props} />
        </div>
    );
}

export function TableHeader({ className, ...props }: ComponentProps<'thead'>) {
    return <thead className={cn('bg-muted/70', className)} {...props} />;
}

export function TableBody({ className, ...props }: ComponentProps<'tbody'>) {
    return <tbody className={cn('[&_tr:last-child]:border-0', className)} {...props} />;
}

export function TableRow({ className, ...props }: ComponentProps<'tr'>) {
    return <tr className={cn('hover:bg-muted/40 border-b transition-colors', className)} {...props} />;
}

export function TableHead({ className, ...props }: ComponentProps<'th'>) {
    return (
        <th
            className={cn('text-muted-foreground h-10 px-3 text-left align-middle text-[13px] font-medium', className)}
            {...props}
        />
    );
}

export function TableCell({ className, ...props }: ComponentProps<'td'>) {
    return <td className={cn('px-3 py-3.5 align-middle', className)} {...props} />;
}
