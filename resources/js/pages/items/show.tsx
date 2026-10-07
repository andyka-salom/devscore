import { Link, router } from '@inertiajs/react';
import { Hand, Pencil, Trash2, CalendarDays, User, Clock, AlertCircle } from 'lucide-react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { DetailList } from '@/components/detail-list';
import { ItemTimeline } from '@/components/items/item-timeline';
import { TransitionActions } from '@/components/items/transition-actions';
import { TriagePanel } from '@/components/items/triage-panel';
import { NoteComposer } from '@/components/notes/note-composer';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatDays } from '@/lib/format';
import type { ItemShowPageProps } from '@/types/item';

export default function ItemShow({ item, timeline, allowed_transitions, can, triage }: ItemShowPageProps) {
    const actions = (
        <div className="flex items-center gap-2">
            {can.update && (
                <Link href={`/items/${item.id}/edit`} className={buttonVariants({ variant: 'outline', size: 'sm', className: 'bg-white shadow-sm' })}>
                    <Pencil className="mr-1.5 size-4" /> Edit Item
                </Link>
            )}
            {can.delete && (
                <ConfirmDialog
                    title={`Hapus ${item.code}?`}
                    description="Item backlog akan dihapus (soft delete)."
                    confirmLabel="Hapus"
                    variant="destructive"
                    onConfirm={() => router.delete(`/items/${item.id}`)}
                    trigger={(open) => (
                        <Button variant="outline" size="sm" onClick={open} aria-label="Hapus item" className="text-red-500 hover:text-red-600 hover:bg-red-50 bg-white shadow-sm">
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                />
            )}
        </div>
    );

    return (
        <AppLayout title={item.code} subtitle={item.project.name} actions={actions}>
            <div className="grid gap-6 lg:grid-cols-3 pb-8">
                <div className="space-y-6 lg:col-span-2">
                    {/* Header Card */}
                    <Card className="overflow-hidden border-slate-200/60 shadow-sm bg-gradient-to-br from-white to-slate-50/50">
                        <div className="h-2 w-full bg-gradient-to-r from-primary to-blue-400" />
                        <CardHeader className="pb-4">
                            <div className="flex flex-wrap items-center gap-2 mb-3">
                                <span className="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs font-bold tracking-wider">{item.code}</span>
                                <Badge tone={item.type.value === 'bug' ? 'danger' : 'info'} className="text-[10px] tracking-wide uppercase px-2 py-0.5">
                                    {item.type.label}
                                </Badge>
                                <StatusBadge status={item.status} />
                            </div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 leading-tight">
                                {item.title}
                            </h1>
                            <div className="flex flex-wrap items-center gap-3 mt-6 pt-2">
                                {can.claim && (
                                    <ConfirmDialog
                                        title={`Claim ${item.code}?`}
                                        description="Item akan ditugaskan ke Anda dan mulai dikerjakan."
                                        confirmLabel="Claim Task"
                                        onConfirm={() =>
                                            router.post(`/items/${item.id}/claim`, {}, { preserveScroll: true })
                                        }
                                        trigger={(open) => (
                                            <Button size="sm" onClick={open} className="shadow-sm">
                                                <Hand className="mr-1.5 size-4" /> Claim Task
                                            </Button>
                                        )}
                                    />
                                )}
                                <TransitionActions itemId={item.id} transitions={allowed_transitions} />
                            </div>
                        </CardHeader>
                        
                        <CardContent className="space-y-8 pt-2">
                            <div className="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
                                <Section title="Deskripsi" body={item.description} />
                            </div>
                            
                            {item.type.value === 'bug' && (
                                <div className="bg-red-50/50 rounded-xl p-5 border border-red-100">
                                    <Section title="Detail Bug" body={item.steps_to_reproduce} />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Timeline & Notes Card */}
                    <Card className="border-slate-200/60 shadow-sm">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Clock className="size-5 text-muted-foreground" />
                                Riwayat & Catatan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6 space-y-6 bg-white">
                            <div className="pl-2">
                                <ItemTimeline entries={timeline} />
                            </div>
                            {can.add_note && (
                                <div className="mt-8 bg-slate-50 rounded-xl p-1 border border-slate-100">
                                    <NoteComposer action={`/items/${item.id}/notes`} />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Sidebar Details */}
                <div className="space-y-6">
                    {triage && (
                        <div className="shadow-sm rounded-xl overflow-hidden border border-slate-200/60 bg-white">
                            <TriagePanel itemId={item.id} triage={triage} canAssign={can.assign} />
                        </div>
                    )}

                    <Card className="border-slate-200/60 shadow-sm">
                        <CardHeader className="bg-slate-50/50 border-b border-slate-100 pb-4">
                            <CardTitle className="text-base flex items-center gap-2">
                                <AlertCircle className="size-4 text-muted-foreground" />
                                Informasi Item
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-5">
                            <DetailList
                                className="sm:grid-cols-1 gap-y-4"
                                entries={[
                                    { label: 'Project', value: <span className="font-medium text-slate-900">{item.project.code} · {item.project.name}</span> },
                                    { label: 'Prioritas', value: <Badge variant="outline" className="text-xs bg-slate-50">{item.priority.label}</Badge> },
                                    { label: 'Difficulty', value: item.difficulty ? <span className="font-semibold">{item.difficulty} Pts</span> : '—' },
                                    { label: 'Estimasi', value: item.estimate_days ? <span className="font-medium">{formatDays(item.estimate_days)}</span> : '—' },
                                    { label: 'Programmer', value: item.assignee ? <div className="flex items-center gap-1.5"><User className="size-3.5 text-muted-foreground"/> {item.assignee.name}</div> : '—' },
                                    { label: 'QA', value: item.qa ? <div className="flex items-center gap-1.5"><User className="size-3.5 text-muted-foreground"/> {item.qa.name}</div> : '—' },
                                    { label: 'Dibuat oleh', value: item.creator ? <div className="flex items-center gap-1.5"><User className="size-3.5 text-muted-foreground"/> {item.creator.name}</div> : '—' },
                                    { label: 'Due date', value: item.due_date ? <div className="flex items-center gap-1.5"><CalendarDays className="size-3.5 text-muted-foreground"/> {formatDate(item.due_date)}</div> : '—' },
                                    { label: 'Mulai dikerjakan', value: formatDateTime(item.started_at) },
                                    { label: 'Disetujui', value: formatDateTime(item.approved_at) },
                                    {
                                        label: 'Gagal QA / Ditolak / Reopen',
                                        value: (
                                            <div className="flex gap-2 items-center text-xs mt-1">
                                                <span className="px-2 py-0.5 rounded bg-red-50 text-red-600 border border-red-100" title="Gagal QA">{item.qa_fail_count}</span>
                                                <span className="px-2 py-0.5 rounded bg-orange-50 text-orange-600 border border-orange-100" title="Ditolak">{item.reject_count}</span>
                                                <span className="px-2 py-0.5 rounded bg-yellow-50 text-yellow-600 border border-yellow-100" title="Dibuka Kembali">{item.reopen_count}</span>
                                            </div>
                                        ),
                                    },
                                ]}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function Section({ title, body }: { title: string; body: string | null }) {
    return (
        <section className="space-y-2">
            <h3 className="text-sm font-bold text-slate-800 tracking-wide uppercase">{title}</h3>
            {body ? (
                <div className="text-slate-600 text-[15px] leading-relaxed whitespace-pre-line">
                    {body}
                </div>
            ) : (
                <p className="text-slate-400 italic text-sm">Tidak ada penjelasan tambahan.</p>
            )}
        </section>
    );
}
