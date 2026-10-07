import { X } from 'lucide-react';
import { useEffect, useRef, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
}

/**
 * Modal berbasis elemen <dialog> native (focus trap & Escape ditangani browser).
 */
export function Dialog({ open, onClose, title, description, children, className }: DialogProps) {
    const ref = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;

        if (open && !dialog.open) dialog.showModal();
        if (!open && dialog.open) dialog.close();
    }, [open]);

    return (
        <dialog
            ref={ref}
            onClose={onClose}
            onClick={(event) => event.target === ref.current && onClose()}
            className={cn(
                'bg-card text-card-foreground m-auto w-full max-w-md rounded-lg border p-0 shadow-lg backdrop:bg-black/40',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-4 border-b p-4">
                <div>
                    <h2 className="text-base font-semibold">{title}</h2>
                    {description && <p className="text-muted-foreground mt-1 text-sm">{description}</p>}
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    className="text-muted-foreground hover:bg-accent rounded-md p-1"
                    aria-label="Tutup"
                >
                    <X className="size-4" />
                </button>
            </div>
            <div className="p-4">{children}</div>
        </dialog>
    );
}
