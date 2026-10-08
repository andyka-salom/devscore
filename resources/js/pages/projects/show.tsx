import { Link } from '@inertiajs/react';
import { ExternalLink, ListTodo, Pencil, Plus } from 'lucide-react';

import { DetailList } from '@/components/detail-list';
import { NoteBody } from '@/components/notes/note-body';
import { NoteComposer } from '@/components/notes/note-composer';
import { ProgressBar } from '@/components/progress-bar';
import { ProjectStatusBadge } from '@/components/projects/project-status-badge';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime } from '@/lib/format';
import type { ProjectShowPageProps } from '@/types/project';

export default function ProjectShow({ project, summary, notes, can }: ProjectShowPageProps) {
    const actions = (
        <div className="flex items-center gap-2">
            {can.create_item && (
                <Link
                    href={`/items/create?project=${project.id}`}
                    className={buttonVariants({ size: 'sm', variant: 'outline' })}
                >
                    <Plus /> Item
                </Link>
            )}
            {can.update && (
                <Link
                    href={`/projects/${project.id}/edit`}
                    className={buttonVariants({ size: 'sm', variant: 'outline' })}
                >
                    <Pencil /> Edit
                </Link>
            )}
        </div>
    );

    return (
        <AppLayout title={project.name} subtitle={`Project ${project.code}`} actions={actions} backUrl="/projects">
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <div>
                                <CardTitle>{project.name}</CardTitle>
                                <CardDescription>
                                    {formatDate(project.start_date)} – {formatDate(project.end_date)}
                                </CardDescription>
                            </div>
                            <ProjectStatusBadge status={project.status} />
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <p className="text-muted-foreground text-sm whitespace-pre-line">
                                {project.description || 'Belum ada deskripsi.'}
                            </p>
                            <div>
                                <div className="mb-2 flex justify-between text-sm">
                                    <span className="font-medium">Progres</span>
                                    <span className="text-muted-foreground">
                                        {summary.done_points} / {summary.total_points} poin · {summary.progress}%
                                    </span>
                                </div>
                                <ProgressBar value={summary.progress} tone="success" label="Progres project" />
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {summary.status_counts.map((row) => (
                                    <Link
                                        key={row.status.value}
                                        href={`/items?project=${project.id}&status=${row.status.value}`}
                                        className="inline-flex items-center gap-1.5"
                                    >
                                        <StatusBadge status={row.status} />
                                        <span className="text-sm font-semibold">{row.count}</span>
                                    </Link>
                                ))}
                            </div>
                            <Link
                                href={`/items?project=${project.id}`}
                                className={buttonVariants({ variant: 'outline', size: 'sm' })}
                            >
                                <ListTodo /> Lihat semua item
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Catatan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {can.add_note && <NoteComposer action={`/projects/${project.id}/notes`} />}
                            {notes.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Belum ada catatan.</p>
                            ) : (
                                <ul className="space-y-4">
                                    {notes.map((note) => (
                                        <li key={note.id}>
                                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                                <span className="font-medium">{note.user.name}</span>
                                                <Badge tone="neutral" className="px-1.5 py-0.5 text-[10px]">
                                                    {note.user.role.label}
                                                </Badge>
                                                <span className="text-muted-foreground text-xs">
                                                    {formatDateTime(note.created_at)}
                                                </span>
                                            </div>
                                            <NoteBody
                                                noteId={note.id}
                                                body={note.body}
                                                canEdit={note.can_edit}
                                                edited={note.edited}
                                                isSystem={note.is_system}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Anggota</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <DetailList
                                className="sm:grid-cols-1"
                                entries={
                                    project.members.length === 0
                                        ? [{ label: 'Anggota', value: 'Belum ada anggota' }]
                                        : project.members.map((member) => ({
                                              label: member.role.label,
                                              value: member.name,
                                          }))
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Links</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {project.links.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Belum ada link.</p>
                            ) : (
                                <ul className="space-y-2">
                                    {project.links.map((link) => (
                                        <li key={link.id ?? link.url}>
                                            <a
                                                href={link.url}
                                                target="_blank"
                                                rel="noreferrer noopener"
                                                className="inline-flex items-center gap-1.5 text-sm font-medium hover:underline"
                                            >
                                                {link.label}
                                                <ExternalLink className="text-muted-foreground size-3.5" />
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
