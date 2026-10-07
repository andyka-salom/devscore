import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Button, type ButtonProps } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input, InputError, Label, Textarea } from '@/components/ui/input';
import type { ItemStatus } from '@/types';
import type { AllowedTransition } from '@/types/item';

const VARIANT: Partial<Record<ItemStatus, ButtonProps['variant']>> = {
    qa_failed: 'destructive',
    rejected: 'destructive',
    cancelled: 'outline',
    on_hold: 'outline',
};

interface TransitionActionsProps {
    itemId: number;
    transitions: AllowedTransition[];
}

/**
 * Tombol aksi transisi status. Daftar aksi berasal dari backend (`allowed_transitions`);
 * transisi yang butuh alasan membuka dialog.
 */
export function TransitionActions({ itemId, transitions }: TransitionActionsProps) {
    const [pending, setPending] = useState<AllowedTransition | null>(null);
    const form = useForm<{ status: ItemStatus | ''; reason: string; attachment: File | null }>({ status: '', reason: '', attachment: null });

    if (transitions.length === 0) return null;

    const submit = (transition: AllowedTransition) => {
        form.transform((data) => ({ ...data, status: transition.value }));
        form.post(`/items/${itemId}/transitions`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setPending(null);
                form.reset();
            },
        });
    };

    const onSubmitReason = (event: FormEvent) => {
        event.preventDefault();
        if (pending) submit(pending);
    };

    const close = () => {
        setPending(null);
        form.reset();
        form.clearErrors();
    };

    return (
        <>
            <div className="flex flex-wrap gap-2">
                {transitions.map((transition) => (
                    <Button
                        key={transition.value}
                        variant={VARIANT[transition.value] ?? 'default'}
                        size="sm"
                        disabled={form.processing}
                        onClick={() => (transition.requires_reason ? setPending(transition) : submit(transition))}
                    >
                        {transition.label}
                    </Button>
                ))}
            </div>
            <InputError message={!pending ? form.errors.status : undefined} className="mt-2" />

            <Dialog
                open={pending !== null}
                onClose={close}
                title={pending?.label ?? ''}
                description="Alasan wajib diisi dan akan tercatat di riwayat item."
            >
                <form onSubmit={onSubmitReason} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="reason">Alasan</Label>
                        <Textarea
                            id="reason"
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                            aria-invalid={!!form.errors.reason}
                            autoFocus
                        />
                        <InputError message={form.errors.reason ?? form.errors.status} />
                    </div>

                    {pending?.value === 'qa_failed' && (
                        <div className="space-y-2">
                            <Label htmlFor="attachment">Lampirkan Gambar Bukti (Opsional)</Label>
                            <Input
                                id="attachment"
                                type="file"
                                accept="image/*"
                                onChange={(e) => form.setData('attachment', e.target.files?.[0] ?? null)}
                            />
                            <InputError message={form.errors.attachment} />
                        </div>
                    )}

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={close}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            variant={pending ? (VARIANT[pending.value] ?? 'default') : 'default'}
                            disabled={form.processing || form.data.reason.trim() === ''}
                        >
                            {pending?.label}
                        </Button>
                    </div>
                </form>
            </Dialog>
        </>
    );
}
