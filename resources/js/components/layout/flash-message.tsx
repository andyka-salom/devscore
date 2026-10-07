import { usePage } from '@inertiajs/react';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

import { cn } from '@/lib/utils';

const AUTO_DISMISS_MS = 4000;

export function FlashMessage() {
    const { flash } = usePage();
    const message = flash.success ?? flash.error;
    const isError = !flash.success && !!flash.error;
    const [dismissed, setDismissed] = useState<string | null>(null);

    useEffect(() => {
        if (!message) return;

        const timer = window.setTimeout(() => setDismissed(message), AUTO_DISMISS_MS);

        return () => window.clearTimeout(timer);
    }, [message]);

    if (!message || dismissed === message) return null;

    const Icon = isError ? XCircle : CheckCircle2;

    return (
        <div
            role="status"
            className={cn(
                'bg-card fixed top-4 right-4 z-50 flex max-w-sm items-start gap-3 rounded-lg border p-4 text-sm shadow-lg',
                isError ? 'border-red-200' : 'border-emerald-200',
            )}
        >
            <Icon className={cn('mt-0.5 size-4 shrink-0', isError ? 'text-red-600' : 'text-emerald-600')} />
            <p className="flex-1">{message}</p>
            <button type="button" onClick={() => setDismissed(message)} aria-label="Tutup">
                <X className="text-muted-foreground size-4" />
            </button>
        </div>
    );
}
