import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface CheckboxProps {
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
    label: ReactNode;
    description?: string;
    className?: string;
    disabled?: boolean;
}

export function Checkbox({ checked, onCheckedChange, label, description, className, disabled }: CheckboxProps) {
    return (
        <label className={cn('flex cursor-pointer items-start gap-2.5 text-sm', disabled && 'opacity-50', className)}>
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(event) => onCheckedChange(event.target.checked)}
                className="accent-primary mt-0.5 size-4 shrink-0 rounded border"
            />
            <span>
                <span className="font-medium">{label}</span>
                {description && <span className="text-muted-foreground block text-xs">{description}</span>}
            </span>
        </label>
    );
}
