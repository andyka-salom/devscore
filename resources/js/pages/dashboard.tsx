import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, Hammer, Inbox, UserRound, Clock, ArrowRight, FolderKanban } from 'lucide-react';

import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ProgressBar } from '@/components/progress-bar';
import AppLayout from '@/layouts/app-layout';
import type { DashboardStats } from '@/types/item';
import type { EnumOption } from '@/types';
import type { ItemStatus } from '@/types/item';

interface DashboardProps {
    stats?: DashboardStats;
    recent_items?: Array<{
        id: number;
        code: string;
        title: string;
        status: EnumOption<ItemStatus>;
        priority: EnumOption<string>;
        project: string;
        updated_at: string;
        url: string;
    }>;
    active_projects?: Array<{
        id: number;
        name: string;
        code: string;
        progress: number;
        total_items: number;
        done_items: number;
        url: string;
    }>;
}

export default function Dashboard({ stats, recent_items, active_projects }: DashboardProps) {
    const user = usePage().props.auth.user;

    return (
        <AppLayout title="Beranda">
            <div className="mb-8 p-6 rounded-2xl bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border border-primary/10 relative overflow-hidden">
                <div className="relative z-10">
                    <h2 className="text-2xl font-bold tracking-tight text-foreground">Selamat Datang, {user?.name} 👋</h2>
                    <p className="text-muted-foreground mt-1">Berikut adalah ringkasan pekerjaan tim dan progres proyek Anda saat ini.</p>
                </div>
                <div className="absolute top-0 right-0 -mt-10 -mr-10 opacity-10">
                    <CheckCircle2 className="w-64 h-64 text-primary" />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
                <StatCard label="Item aktif saya" value={stats?.my_active ?? null} icon={UserRound} />
                <StatCard label="Backlog" value={stats?.backlog ?? null} icon={Inbox} hint="Menunggu triage" />
                <StatCard label="Sedang dikerjakan" value={stats?.in_progress ?? null} icon={Hammer} />
                <StatCard label="Selesai bulan ini" value={stats?.done_this_month ?? null} icon={CheckCircle2} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card className="flex flex-col">
                    <CardHeader className="border-b bg-slate-50/50 pb-4">
                        <CardTitle className="text-base flex items-center gap-2">
                            <Clock className="w-4 h-4 text-primary" />
                            Aktivitas Terakhir Anda
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 flex-1">
                        {(!recent_items || recent_items.length === 0) ? (
                            <div className="p-8 text-center text-muted-foreground text-sm flex flex-col items-center justify-center h-full">
                                <Inbox className="w-8 h-8 mb-2 opacity-20" />
                                Belum ada aktivitas.
                            </div>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {recent_items.map((item) => (
                                    <li key={item.id}>
                                        <Link 
                                            href={item.url} 
                                            className="group flex flex-col gap-1.5 p-4 hover:bg-slate-50 transition-colors"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-semibold text-muted-foreground">{item.code} • {item.project}</span>
                                                <span className="text-[10px] text-muted-foreground flex items-center gap-1">
                                                    {item.updated_at}
                                                </span>
                                            </div>
                                            <div className="flex items-start justify-between gap-3">
                                                <h4 className="text-sm font-medium leading-tight group-hover:text-primary transition-colors line-clamp-1">{item.title}</h4>
                                            </div>
                                            <div className="flex items-center gap-2 mt-1">
                                                <StatusBadge status={item.status} />
                                                <Badge tone={item.priority.value === 'high' || item.priority.value === 'critical' ? 'danger' : 'neutral'} className="text-[10px] px-1.5 py-0">
                                                    {item.priority.label}
                                                </Badge>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="p-3 border-t bg-slate-50/50">
                            <Link href="/items" className="text-xs font-medium text-primary hover:underline flex items-center justify-center w-full">
                                Lihat Semua Item <ArrowRight className="w-3 h-3 ml-1" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card className="flex flex-col">
                    <CardHeader className="border-b bg-slate-50/50 pb-4">
                        <CardTitle className="text-base flex items-center gap-2">
                            <FolderKanban className="w-4 h-4 text-primary" />
                            Proyek Aktif
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 flex-1">
                        {(!active_projects || active_projects.length === 0) ? (
                            <div className="p-8 text-center text-muted-foreground text-sm flex flex-col items-center justify-center h-full">
                                <FolderKanban className="w-8 h-8 mb-2 opacity-20" />
                                Belum ada proyek aktif.
                            </div>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {active_projects.map((project) => (
                                    <li key={project.id}>
                                        <Link 
                                            href={project.url} 
                                            className="group flex flex-col gap-3 p-5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <span className="text-xs font-semibold text-muted-foreground">{project.code}</span>
                                                    <h4 className="text-sm font-medium leading-tight group-hover:text-primary transition-colors">{project.name}</h4>
                                                </div>
                                                <div className="text-right">
                                                    <span className="text-xs font-bold text-primary">{project.progress}%</span>
                                                    <p className="text-[10px] text-muted-foreground mt-0.5">{project.done_items} / {project.total_items} Selesai</p>
                                                </div>
                                            </div>
                                            <ProgressBar value={project.progress} tone="success" className="h-1.5" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div className="p-3 border-t bg-slate-50/50 mt-auto">
                            <Link href="/projects" className="text-xs font-medium text-primary hover:underline flex items-center justify-center w-full">
                                Lihat Semua Proyek <ArrowRight className="w-3 h-3 ml-1" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
