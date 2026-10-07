import { ArrowRight, MessageSquare, RefreshCw } from 'lucide-react';

import { NoteBody } from '@/components/notes/note-body';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { formatDateTime } from '@/lib/format';
import type { TimelineEntry } from '@/types/item';

/** Timeline gabungan perubahan status + notes, urut kronologis. */
export function ItemTimeline({ entries }: { entries: TimelineEntry[] }) {
    if (entries.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-10 text-center border border-dashed rounded-xl bg-slate-50/50">
                <MessageSquare className="size-8 text-slate-300 mb-2" />
                <p className="text-muted-foreground text-sm">Belum ada riwayat aktivitas.</p>
            </div>
        );
    }

    return (
        <div className="relative pl-3">
            {/* The main vertical line */}
            <div className="absolute top-3 bottom-0 left-[27px] w-px bg-slate-200"></div>

            <ol className="relative space-y-8">
                {entries.map((entry) => {
                    const Icon = entry.kind === 'status' ? RefreshCw : MessageSquare;
                    const isSystem = entry.kind === 'note' && entry.is_system;

                    return (
                        <li key={entry.id} className="relative pl-12 group">
                            {/* Timeline Node/Icon */}
                            <span className={`absolute top-0.5 left-4 flex size-[22px] items-center justify-center rounded-full border ring-4 ring-white ${isSystem ? 'bg-amber-100 border-amber-200 text-amber-600' : 'bg-slate-50 border-slate-200 text-slate-500'} group-hover:scale-110 transition-transform`}>
                                <Icon className="size-3" />
                            </span>

                            {/* Meta Header */}
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm mb-1">
                                <span className="font-semibold text-slate-800">{entry.user.name}</span>
                                <Badge variant="outline" className="px-1.5 py-0 bg-slate-50 text-[9px] uppercase tracking-wider text-slate-500 border-slate-200">
                                    {entry.user.role.label}
                                </Badge>
                                <span className="text-muted-foreground text-[11px] font-medium ml-1">
                                    {formatDateTime(entry.at)}
                                </span>
                            </div>

                            {/* Content: Status Change */}
                            {entry.kind === 'status' && entry.to && (
                                <div className="mt-2.5 flex flex-wrap items-center gap-2 bg-slate-50/50 border border-slate-100 rounded-lg p-2.5 w-fit shadow-sm">
                                    {entry.from ? (
                                        <>
                                            <StatusBadge status={entry.from} className="scale-90 origin-left" />
                                            <ArrowRight className="text-slate-300 size-3.5" />
                                            <StatusBadge status={entry.to} className="scale-90 origin-left" />
                                        </>
                                    ) : (
                                        <StatusBadge status={entry.to} />
                                    )}
                                </div>
                            )}

                            {/* Content: Status Body Text */}
                            {entry.kind === 'status' && (entry.body || entry.attachment_url) && (
                                <div className="mt-3 relative">
                                    <div className="absolute left-4 top-0 bottom-0 w-1 bg-slate-100 rounded-full"></div>
                                    <div className="pl-8 text-slate-600 text-sm whitespace-pre-line leading-relaxed">
                                        {entry.body && <p>{entry.body}</p>}
                                        {entry.attachment_url && (
                                            <div className="mt-2">
                                                <img src={entry.attachment_url} alt="Attachment" className="max-w-md w-full rounded-lg border border-slate-200 shadow-sm" />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Content: Note */}
                            {entry.kind === 'note' && entry.note_id !== null && entry.body && (
                                <div className="mt-2">
                                    <NoteBody
                                        noteId={entry.note_id}
                                        body={entry.body}
                                        canEdit={entry.can_edit}
                                        edited={entry.edited}
                                        isSystem={entry.is_system}
                                    />
                                </div>
                            )}
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
