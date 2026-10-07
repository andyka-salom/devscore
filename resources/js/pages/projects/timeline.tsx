import { Link } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { buttonVariants } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import type { EnumOption } from '@/types';
import type { ProjectStatus } from '@/types/project';

interface TimelineProject {
    id: number;
    name: string;
    status: EnumOption<ProjectStatus>;
    start_date: string | null;
    end_date: string | null;
}

interface TimelineProps {
    projects: TimelineProject[];
}

export default function ProjectTimeline({ projects }: TimelineProps) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const validProjects = projects
        .filter((p) => p.start_date && p.end_date)
        .map((p) => {
            const start = new Date(p.start_date!);
            const end = new Date(p.end_date!);
            const duration = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
            
            let visualStatus = 'In Progress';
            let colorClass = 'bg-blue-500';
            
            if (p.status.value === 'completed') {
                visualStatus = 'Nearly Complete';
                colorClass = 'bg-green-500';
            } else if (end < today) {
                visualStatus = 'Overdue';
                colorClass = 'bg-red-500';
            } else if (end.getTime() - today.getTime() <= 14 * 24 * 60 * 60 * 1000) {
                visualStatus = 'Approaching Deadline';
                colorClass = 'bg-orange-500';
            } else {
                visualStatus = 'In Progress';
                colorClass = 'bg-blue-500';
            }

            return { ...p, start, end, duration, visualStatus, colorClass };
        });

    let minDate = new Date();
    let maxDate = new Date();
    if (validProjects.length > 0) {
        minDate = new Date(Math.min(...validProjects.map((p) => p.start.getTime())));
        maxDate = new Date(Math.max(...validProjects.map((p) => p.end.getTime())));
    }

    minDate.setMonth(minDate.getMonth() - 1);
    maxDate.setMonth(maxDate.getMonth() + 2);
    minDate.setDate(1);
    maxDate.setDate(1);

    const totalDays = Math.round((maxDate.getTime() - minDate.getTime()) / (1000 * 60 * 60 * 24)) || 1;

    const months: { year: number; month: string; days: number }[] = [];
    let current = new Date(minDate);
    while (current < maxDate) {
        const year = current.getFullYear();
        const month = current.toLocaleString('en-US', { month: 'long' });
        const daysInMonth = new Date(year, current.getMonth() + 1, 0).getDate();
        months.push({ year, month, days: daysInMonth });
        current.setMonth(current.getMonth() + 1);
    }

    return (
        <AppLayout
            title="Timeline View"
            actions={
                <div className="flex items-center gap-2">
                    <span className="text-muted-foreground text-sm flex items-center mr-2">
                        <span className="mr-1 opacity-50">◎</span> Read Only Mode
                    </span>
                    <Link href="/projects" className={buttonVariants({ variant: 'outline', size: 'sm' })}>
                        <LayoutGrid className="mr-1 size-4" /> Card View
                    </Link>
                </div>
            }
        >
            <Card className="overflow-hidden bg-white shadow-sm border border-slate-200 rounded-lg">
                <div className="overflow-x-auto">
                    <div className="min-w-[1000px]">
                        <div className="flex border-b border-slate-200 bg-slate-50/50">
                            <div className="w-[360px] flex-shrink-0 flex text-sm font-semibold text-slate-500 divide-x divide-slate-200">
                                <div className="w-[200px] p-3 flex items-center justify-center">Project Name</div>
                                <div className="w-[100px] p-3 flex items-center justify-center">Status</div>
                                <div className="w-[60px] p-3 flex items-center justify-center">Duration</div>
                            </div>
                            <div className="flex-1 flex flex-col relative text-sm font-medium text-slate-500">
                                <div className="flex absolute top-0 w-full text-[10px] text-center border-b border-slate-200 h-6 items-center">
                                    {months.map((m, i) => {
                                        const showYear = i === 0 || m.month === 'January';
                                        return (
                                            <div key={i} style={{ width: `${(m.days / totalDays) * 100}%` }}>
                                                {showYear ? m.year : ''}
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="flex mt-6 h-8 divide-x divide-slate-200 items-center">
                                    {months.map((m, i) => (
                                        <div key={i} className="text-center truncate px-1 text-xs w-full" style={{ width: `${(m.days / totalDays) * 100}%` }}>
                                            {m.month}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="divide-y divide-slate-100">
                            {validProjects.map((project) => {
                                const leftOffset = ((project.start.getTime() - minDate.getTime()) / (1000 * 60 * 60 * 24) / totalDays) * 100;
                                const width = (project.duration / totalDays) * 100;

                                return (
                                    <div key={project.id} className="flex hover:bg-slate-50 transition-colors">
                                        <div className="w-[360px] flex-shrink-0 flex text-sm divide-x divide-slate-100">
                                            <div className="w-[200px] p-3 truncate font-medium text-slate-700">
                                                {project.name}
                                            </div>
                                            <div className="w-[100px] p-3 text-xs text-center text-slate-600 flex items-center justify-center">
                                                {project.visualStatus}
                                            </div>
                                            <div className="w-[60px] p-3 text-xs text-center text-slate-500 flex items-center justify-center">
                                                {project.duration}
                                            </div>
                                        </div>
                                        <div className="flex-1 relative border-l border-slate-200 flex items-center py-2 min-h-[44px]">
                                            <div className="absolute inset-0 flex divide-x divide-slate-100/50 pointer-events-none">
                                                {months.map((m, i) => (
                                                    <div key={i} style={{ width: `${(m.days / totalDays) * 100}%` }}></div>
                                                ))}
                                            </div>
                                            
                                            <div 
                                                className={`h-7 rounded-sm ${project.colorClass} text-white text-xs flex items-center px-2 truncate absolute z-10 hover:opacity-90 transition-opacity cursor-pointer`}
                                                style={{ left: `${leftOffset}%`, width: `${width}%` }}
                                                title={`${project.name}: ${project.start.toLocaleDateString()} to ${project.end.toLocaleDateString()}`}
                                            >
                                                {width > 5 && project.name}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </Card>

            <Card className="mt-6 p-4 border border-slate-200">
                <h4 className="font-semibold text-sm mb-4 text-slate-800">Status Legend</h4>
                <div className="flex items-center gap-8 text-sm text-slate-600">
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-blue-500"></div>
                        In Progress
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-green-500"></div>
                        Nearly Complete
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-orange-500"></div>
                        Approaching Deadline
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-red-500"></div>
                        Overdue
                    </div>
                </div>
            </Card>
        </AppLayout>
    );
}
