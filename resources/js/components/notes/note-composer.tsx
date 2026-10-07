import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { InputError, Textarea } from '@/components/ui/input';

interface NoteComposerProps {
    /** Endpoint POST, mis. /items/12/notes */
    action: string;
    placeholder?: string;
}

/** Form tambah catatan (item maupun project). */
export function NoteComposer({ action, placeholder = 'Tulis catatan…' }: NoteComposerProps) {
    const form = useForm({ body: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="relative">
                <Textarea
                    rows={3}
                    value={form.data.body}
                    onChange={(event) => form.setData('body', event.target.value)}
                    placeholder={placeholder}
                    aria-label="Catatan"
                    aria-invalid={!!form.errors.body}
                    className="resize-none bg-white border-slate-200 focus:border-primary shadow-sm rounded-xl py-3 px-4 w-full text-sm"
                />
            </div>
            <InputError message={form.errors.body} />
            <div className="flex justify-end">
                <Button type="submit" size="sm" disabled={form.processing || form.data.body.trim() === ''} className="rounded-full px-5 shadow-sm">
                    Kirim Catatan
                </Button>
            </div>
        </form>
    );
}
