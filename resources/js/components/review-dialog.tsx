import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/input';

export type ReviewMode = 'approve' | 'reject' | 'escalate';

interface ReviewDialogProps {
    /** URL dasar entitas, mis. `/bug-reports/12`; aksi ditambahkan sebagai suffix. */
    baseUrl: string;
    mode: ReviewMode;
    /** Nama entitas pada judul, mis. "permintaan" atau "temuan bug". */
    subject: string;
    /** Kalimat penjelasan dampak aksi. */
    description: string;
    onClose: () => void;
}

const SUBMIT_LABEL: Record<ReviewMode, string> = {
    approve: 'Setujui',
    reject: 'Tolak',
    escalate: 'Teruskan',
};

const TITLE_VERB: Record<ReviewMode, string> = {
    approve: 'Setujui',
    reject: 'Tolak',
    escalate: 'Teruskan ke Chief:',
};

const PLACEHOLDER: Record<ReviewMode, string> = {
    approve: 'Catatan (opsional)',
    reject: 'Alasan penolakan',
    escalate: 'Catatan untuk Chief (opsional)',
};

/** Dialog catatan untuk aksi review (setujui / tolak / teruskan ke Chief). Alasan wajib hanya saat tolak. */
export function ReviewDialog({ baseUrl, mode, subject, description, onClose }: ReviewDialogProps) {
    const [note, setNote] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        if (mode === 'reject' && note.trim() === '') {
            setError('Alasan penolakan wajib diisi.');
            return;
        }

        router.post(
            `${baseUrl}/${mode}`,
            { note },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: onClose,
                onError: (errors) => setError(Object.values(errors)[0]),
            },
        );
    };

    return (
        <Dialog open onClose={onClose} title={`${TITLE_VERB[mode]} ${subject}`} description={description}>
            <div className="space-y-3">
                <Textarea
                    rows={3}
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    placeholder={PLACEHOLDER[mode]}
                    aria-invalid={!!error}
                />
                {error && <p className="text-destructive text-sm">{error}</p>}
                <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button variant={mode === 'reject' ? 'destructive' : 'default'} disabled={processing} onClick={submit}>
                        {SUBMIT_LABEL[mode]}
                    </Button>
                </div>
            </div>
        </Dialog>
    );
}
