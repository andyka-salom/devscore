import { router, useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { InputError, Textarea } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface NoteBodyProps {
    noteId: number;
    body: string;
    canEdit: boolean;
    edited: boolean;
    isSystem?: boolean;
}

/**
 * Isi catatan dengan edit/hapus inline. `canEdit` dihitung backend (penulis, ≤ 15 menit).
 */
export function NoteBody({ noteId, body, canEdit, edited, isSystem = false }: NoteBodyProps) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ body });

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/notes/${noteId}`, { preserveScroll: true, onSuccess: () => setEditing(false) });
    };

    if (editing) {
        return (
            <form onSubmit={save} className="mt-2 space-y-2">
                <Textarea
                    rows={3}
                    value={form.data.body}
                    onChange={(event) => form.setData('body', event.target.value)}
                    aria-label="Ubah catatan"
                />
                <InputError message={form.errors.body} />
                <div className="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                            form.reset();
                            setEditing(false);
                        }}
                    >
                        Batal
                    </Button>
                    <Button size="sm" type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </div>
            </form>
        );
    }

    return (
        <div className="group mt-2 relative">
            {/* Note bubble */}
            <div
                className={cn(
                    'rounded-xl px-4 py-3 text-sm whitespace-pre-line shadow-sm transition-all',
                    isSystem 
                        ? 'border border-amber-200/50 bg-amber-50/50 text-amber-900' 
                        : 'border border-slate-200/60 bg-white hover:border-slate-300'
                )}
            >
                {body}
            </div>
            
            {/* Actions/Meta */}
            <div className="text-muted-foreground mt-2 flex items-center gap-4 text-xs font-medium pl-1 opacity-80 group-hover:opacity-100 transition-opacity">
                {isSystem && <span className="text-amber-600 flex items-center gap-1.5"><div className="size-1.5 rounded-full bg-amber-400"></div> Catatan sistem</span>}
                {edited && <span className="italic text-slate-400">(diedit)</span>}
                {canEdit && (
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setEditing(true)}
                            className="text-slate-400 hover:text-primary inline-flex items-center gap-1.5 transition-colors"
                        >
                            <Pencil className="size-3" /> Edit
                        </button>
                        <ConfirmDialog
                            title="Hapus catatan?"
                            description="Catatan yang dihapus tidak bisa dikembalikan."
                            confirmLabel="Hapus"
                            variant="destructive"
                            onConfirm={() => router.delete(`/notes/${noteId}`, { preserveScroll: true })}
                            trigger={(open) => (
                                <button
                                    type="button"
                                    onClick={open}
                                    className="text-slate-400 hover:text-red-500 inline-flex items-center gap-1.5 transition-colors"
                                >
                                    <Trash2 className="size-3" /> Hapus
                                </button>
                            )}
                        />
                    </div>
                )}
            </div>
        </div>
    );
}
