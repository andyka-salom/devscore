import type { ReactNode } from 'react';

import { InputError, Label } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface FormFieldProps {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: ReactNode;
}

/** Label + kontrol + pesan error/hint per field. */
export function FormField({ label, htmlFor, error, hint, required, className, children }: FormFieldProps) {
    return (
        <div className={cn('space-y-2', className)}>
            <Label htmlFor={htmlFor}>
                {label}
                {required && <span className="text-destructive ml-0.5">*</span>}
            </Label>
            {children}
            {hint && !error && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
