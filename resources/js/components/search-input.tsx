import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

interface SearchInputProps {
    value: string | null;
    onSearch: (value: string | null) => void;
    placeholder?: string;
    className?: string;
    debounceMs?: number;
}

/** Input pencarian dengan debounce; mengirim null bila kosong. */
export function SearchInput({ value, onSearch, placeholder = 'Cari…', className, debounceMs = 400 }: SearchInputProps) {
    const [text, setText] = useState(value ?? '');
    const onSearchRef = useRef(onSearch);
    const lastSent = useRef(value ?? '');

    useEffect(() => {
        onSearchRef.current = onSearch;
    }, [onSearch]);

    useEffect(() => {
        const trimmed = text.trim();
        if (trimmed === lastSent.current) return;

        const timer = window.setTimeout(() => {
            lastSent.current = trimmed;
            onSearchRef.current(trimmed === '' ? null : trimmed);
        }, debounceMs);

        return () => window.clearTimeout(timer);
    }, [text, debounceMs]);

    return (
        <label className={cn('relative block', className)}>
            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
            <input
                type="search"
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder={placeholder}
                className="bg-card focus-visible:ring-ring placeholder:text-muted-foreground h-10 w-full rounded-md border pr-3 pl-9 text-sm shadow-xs outline-none focus-visible:ring-2"
            />
        </label>
    );
}
