import { ChevronDown } from 'lucide-react';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types';

type SelectProps<T extends string | number> = Omit<ComponentProps<'select'>, 'value' | 'onChange'> & {
    options: SelectOption<T>[];
    value: T | null | '';
    onValueChange: (value: T | null) => void;
    /** Label opsi kosong; bila diisi, user bisa memilih "tidak ada". */
    placeholder?: string;
};

/**
 * Select native bergaya shadcn. Nilai angka dikembalikan sebagai number.
 */
export function Select<T extends string | number>({
    options,
    value,
    onValueChange,
    placeholder,
    className,
    ...props
}: SelectProps<T>) {
    const numeric = options.length > 0 && typeof options[0].value === 'number';

    return (
        <div className={cn('relative', className)}>
            <select
                value={value === null ? '' : String(value)}
                onChange={(event) => {
                    const raw = event.target.value;
                    onValueChange(raw === '' ? null : ((numeric ? Number(raw) : raw) as T));
                }}
                className="border-input bg-card focus-visible:ring-ring aria-invalid:border-destructive h-10 w-full appearance-none rounded-md border pr-9 pl-3 text-sm shadow-xs outline-none focus-visible:ring-2 disabled:opacity-50"
                {...props}
            >
                {placeholder !== undefined && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={String(option.value)}>
                        {option.label}
                    </option>
                ))}
            </select>
            <ChevronDown className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2" />
        </div>
    );
}
