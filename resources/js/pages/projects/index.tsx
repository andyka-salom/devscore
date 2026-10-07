import { Link } from '@inertiajs/react';
import { FolderKanban, Plus, Users, CalendarRange, ChevronRight } from 'lucide-react';

import { ProjectStatusBadge } from '@/components/projects/project-status-badge';
import { ProjectTimelineChart } from '@/components/projects/project-timeline-chart';
import { buttonVariants } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import type { ProjectSummary } from '@/types/project';

interface ProjectIndexProps {
    projects: ProjectSummary[];
    can: { create: boolean };
}

export default function ProjectIndex({ projects, can }: ProjectIndexProps) {
    return (
        <AppLayout
            title="Project"
            actions={
                <div className="flex items-center gap-2">
                    <Link href="/projects/timeline" className={buttonVariants({ variant: 'outline', size: 'sm' })}>
                        <CalendarRange className="mr-1 size-4" /> Mode Penuh Timeline
                    </Link>
                    {can.create && (
                        <Link href="/projects/create" className={buttonVariants({ size: 'sm' })}>
                            <Plus className="mr-1 size-4" /> Project Baru
                        </Link>
                    )}
                </div>
            }
        >
            <div className="space-y-8 pb-8">
                {/* Hero Section */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-transparent px-8 py-8 sm:py-10 border border-primary/10">
                    <div className="relative z-10">
                        <h2 className="text-2xl font-bold tracking-tight mb-2">Daftar Project</h2>
                        <p className="text-muted-foreground max-w-2xl text-sm">
                            Kelola semua project tim development di sini. Pantau status, perkembangan item, dan timeline pengerjaan agar selalu tepat waktu.
                        </p>
                    </div>
                    <div className="absolute -right-12 -top-12 size-64 rounded-full bg-primary/10 blur-3xl"></div>
                    <div className="absolute -bottom-16 right-32 size-48 rounded-full bg-blue-500/10 blur-2xl"></div>
                </div>

                {/* Projects Grid */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold tracking-tight">Project Aktif</h3>
                    {projects.length === 0 ? (
                        <Card className="text-muted-foreground flex flex-col items-center justify-center gap-3 py-16 text-sm border-dashed">
                            <FolderKanban className="size-10 stroke-1 opacity-50" />
                            <p>Belum ada project yang bisa Anda akses.</p>
                            {can.create && (
                                <Link href="/projects/create" className={buttonVariants({ variant: 'outline', size: 'sm', className: 'mt-2' })}>
                                    Buat Project Pertama
                                </Link>
                            )}
                        </Card>
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {projects.map((project) => {
                                const progress = project.items_count > 0 
                                    ? Math.round(((project.items_count - project.open_items_count) / project.items_count) * 100) 
                                    : 0;
                                
                                return (
                                    <Link key={project.id} href={project.url} className="group">
                                        <Card className="h-full flex flex-col transition-all hover:shadow-md hover:border-primary/30 bg-white/50 hover:bg-white p-5 border-slate-200">
                                            <div className="flex items-start justify-between gap-3 mb-4">
                                                <div className="min-w-0">
                                                    <p className="text-primary text-[11px] font-bold tracking-wider uppercase mb-1">{project.code}</p>
                                                    <h3 className="truncate font-semibold text-base group-hover:text-primary transition-colors">{project.name}</h3>
                                                </div>
                                                <ProjectStatusBadge status={project.status} />
                                            </div>
                                            
                                            <div className="flex items-center text-muted-foreground text-[11px] font-medium bg-slate-50 w-fit px-2 py-1 rounded-md mb-5">
                                                <CalendarRange className="mr-1.5 size-3" />
                                                {formatDate(project.start_date)} – {formatDate(project.end_date)}
                                            </div>

                                            <div className="mt-auto space-y-4">
                                                <div className="space-y-1.5">
                                                    <div className="flex justify-between text-xs font-medium">
                                                        <span>Progress Item</span>
                                                        <span>{progress}%</span>
                                                    </div>
                                                    <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                                        <div 
                                                            className="h-full bg-primary rounded-full transition-all duration-500 ease-out"
                                                            style={{ width: `${progress}%` }}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between pt-4 border-t border-slate-100">
                                                    <div className="flex items-center gap-4 text-xs">
                                                        <span className="flex items-center font-medium">
                                                            <span className={project.open_items_count > 0 ? "text-amber-600 mr-1" : "text-green-600 mr-1"}>
                                                                {project.open_items_count}
                                                            </span>
                                                            <span className="text-muted-foreground">/ {project.items_count} sisa</span>
                                                        </span>
                                                        <span className="text-muted-foreground flex items-center gap-1.5 bg-slate-100 px-2 py-0.5 rounded-full">
                                                            <Users className="size-3" /> {project.members_count}
                                                        </span>
                                                    </div>
                                                    <ChevronRight className="size-4 text-slate-300 group-hover:text-primary transition-colors group-hover:translate-x-0.5" />
                                                </div>
                                            </div>
                                        </Card>
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Timeline Section */}
                {projects.length > 0 && (
                    <div className="space-y-4 pt-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold tracking-tight">Timeline Project</h3>
                            <p className="text-xs text-muted-foreground">Ikhtisar durasi pengerjaan seluruh project</p>
                        </div>
                        <ProjectTimelineChart projects={projects} />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
