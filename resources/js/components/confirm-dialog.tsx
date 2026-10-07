import { useState, type ReactNode } from 'react';

import { Button, type ButtonProps } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';

interface ConfirmDialogProps {
    title: string;
    description: string;
    confirmLabel: string;
    onConfirm: () => void;
    processing?: boolean;
    variant?: ButtonProps['variant'];
    /** Elemen pemicu; menerima fungsi untuk membuka dialog. */
    trigger: (open: () => void) => ReactNode;
}

/** Konfirmasi sebelum aksi yang tidak bisa dibatalkan. */
export function ConfirmDialog({
    title,
    description,
    confirmLabel,
    onConfirm,
    processing,
    variant = 'default',
    trigger,
}: ConfirmDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <>
            {trigger(() => setOpen(true))}
            <Dialog open={open} onClose={() => setOpen(false)} title={title} description={description}>
                <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Batal
                    </Button>
                    <Button
                        variant={variant}
                        disabled={processing}
                        onClick={() => {
                            onConfirm();
                            setOpen(false);
                        }}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </Dialog>
        </>
    );
}
