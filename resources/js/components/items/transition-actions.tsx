import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Button, type ButtonProps } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input, InputError, Label, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import type { ItemStatus } from '@/types';
import type { AllowedTransition, QaFinding } from '@/types/item';

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

interface TransitionForm {
    status: ItemStatus | '';
    reason: string;
    qa_finding: QaFinding | '';
    attachment: File | null;
}

const needsDialog = (transition: AllowedTransition) => transition.requires_reason || transition.qa_findings.length > 0;

/**
 * Tombol aksi transisi status. Daftar aksi berasal dari backend (`allowed_transitions`);
 * transisi yang butuh alasan atau hasil testing QA membuka dialog.
 */
export function TransitionActions({ itemId, transitions }: TransitionActionsProps) {
    const [pending, setPending] = useState<AllowedTransition | null>(null);
    const form = useForm<TransitionForm>({ status: '', reason: '', qa_finding: '', attachment: null });

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

    const open = (transition: AllowedTransition) => {
        // Lulus QA default "tanpa bug"; gagal QA wajib memilih kategori bug.
        form.setData('qa_finding', transition.value === 'qa_passed' ? (transition.qa_findings[0]?.value ?? '') : '');
        setPending(transition);
    };

    const onSubmitDialog = (event: FormEvent) => {
        event.preventDefault();
        if (pending) submit(pending);
    };

    const close = () => {
        setPending(null);
        form.reset();
        form.clearErrors();
    };

    const incomplete =
        pending !== null &&
        ((pending.requires_reason && form.data.reason.trim() === '') ||
            (pending.qa_findings.length > 0 && form.data.qa_finding === ''));

    return (
        <>
            <div className="flex flex-wrap gap-2">
                {transitions.map((transition) => (
                    <Button
                        key={transition.value}
                        variant={VARIANT[transition.value] ?? 'default'}
                        size="sm"
                        disabled={form.processing}
                        onClick={() => (needsDialog(transition) ? open(transition) : submit(transition))}
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
                description={
                    pending?.requires_reason
                        ? 'Alasan wajib diisi dan akan tercatat di riwayat item.'
                        : 'Hasil testing akan tercatat di riwayat item dan dipakai untuk KPI kualitas.'
                }
            >
                <form onSubmit={onSubmitDialog} className="space-y-4">
                    {pending && pending.qa_findings.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="qa_finding">Hasil testing (bug paling berat)</Label>
                            <Select<QaFinding>
                                id="qa_finding"
                                options={pending.qa_findings}
                                value={form.data.qa_finding}
                                onValueChange={(value) => form.setData('qa_finding', value ?? '')}
                                placeholder="Pilih kategori bug"
                                aria-invalid={!!form.errors.qa_finding}
                            />
                            <p className="text-muted-foreground text-xs">
                                Perubahan kebutuhan di luar scope awal tidak dihitung sebagai bug programmer.
                            </p>
                            <InputError message={form.errors.qa_finding} />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="reason">{pending?.requires_reason ? 'Alasan' : 'Catatan (opsional)'}</Label>
                        <Textarea
                            id="reason"
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                            aria-invalid={!!form.errors.reason}
                            autoFocus={!pending?.qa_findings.length}
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
                            disabled={form.processing || incomplete}
                        >
                            {pending?.label}
                        </Button>
                    </div>
                </form>
            </Dialog>
        </>
    );
}
