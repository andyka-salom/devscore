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

export function ProjectTimelineChart({ projects }: TimelineProps) {
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

    if (validProjects.length === 0) {
        return null; // Return nothing if no valid projects
    }

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
        const month = current.toLocaleString('id-ID', { month: 'short' });
        const daysInMonth = new Date(year, current.getMonth() + 1, 0).getDate();
        months.push({ year, month, days: daysInMonth });
        current.setMonth(current.getMonth() + 1);
    }

    return (
        <div className="w-full space-y-4">
            <Card className="overflow-hidden bg-white shadow-sm border border-slate-200 rounded-xl">
                <div className="overflow-x-auto scrollbar-thin">
                    <div className="min-w-[800px]">
                        <div className="flex border-b border-slate-200 bg-slate-50/80">
                            <div className="w-[300px] flex-shrink-0 flex text-xs font-semibold text-slate-500 divide-x divide-slate-200">
                                <div className="w-[180px] p-3 flex items-center">Nama Project</div>
                                <div className="w-[120px] p-3 flex items-center justify-center">Status</div>
                            </div>
                            <div className="flex-1 flex flex-col relative text-xs font-medium text-slate-500">
                                <div className="flex absolute top-0 w-full text-[10px] text-center border-b border-slate-200 h-6 items-center">
                                    {months.map((m, i) => {
                                        const showYear = i === 0 || m.month === 'Jan';
                                        return (
                                            <div key={i} style={{ width: `${(m.days / totalDays) * 100}%` }}>
                                                {showYear ? m.year : ''}
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="flex mt-6 h-8 divide-x divide-slate-200 items-center">
                                    {months.map((m, i) => (
                                        <div key={i} className="text-center truncate px-1 text-[10px] uppercase tracking-wider w-full" style={{ width: `${(m.days / totalDays) * 100}%` }}>
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
                                        <div className="w-[300px] flex-shrink-0 flex text-sm divide-x divide-slate-100">
                                            <div className="w-[180px] p-3 truncate font-medium text-slate-700 text-xs">
                                                {project.name}
                                            </div>
                                            <div className="w-[120px] p-3 text-[10px] text-center flex items-center justify-center">
                                                <span className={`px-2 py-0.5 rounded-full text-white ${project.colorClass}`}>{project.visualStatus}</span>
                                            </div>
                                        </div>
                                        <div className="flex-1 relative border-l border-slate-200 flex items-center py-2 min-h-[44px]">
                                            <div className="absolute inset-0 flex divide-x divide-slate-100/50 pointer-events-none">
                                                {months.map((m, i) => (
                                                    <div key={i} style={{ width: `${(m.days / totalDays) * 100}%` }}></div>
                                                ))}
                                            </div>
                                            
                                            <div 
                                                className={`h-6 rounded-md ${project.colorClass} text-white text-[10px] font-medium flex items-center justify-center px-2 truncate absolute z-10 hover:opacity-90 transition-opacity cursor-pointer shadow-sm`}
                                                style={{ left: `${leftOffset}%`, width: `${width}%` }}
                                                title={`${project.name}: ${project.start.toLocaleDateString()} to ${project.end.toLocaleDateString()}`}
                                            >
                                                {width > 8 && project.duration + ' hr'}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </Card>

            <div className="flex items-center gap-6 text-xs text-slate-600 justify-end">
                <div className="flex items-center gap-1.5">
                    <div className="size-2.5 rounded-full bg-blue-500"></div>
                    In Progress
                </div>
                <div className="flex items-center gap-1.5">
                    <div className="size-2.5 rounded-full bg-green-500"></div>
                    Completed
                </div>
                <div className="flex items-center gap-1.5">
                    <div className="size-2.5 rounded-full bg-orange-500"></div>
                    Near Deadline
                </div>
                <div className="flex items-center gap-1.5">
                    <div className="size-2.5 rounded-full bg-red-500"></div>
                    Overdue
                </div>
            </div>
        </div>
    );
}
