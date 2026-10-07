import { Link, usePage } from '@inertiajs/react';
import { PanelLeftClose, PanelRightClose, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

import { badgeCount, isAllowed, navigation, type NavItem } from '@/components/layout/navigation';
import { UserMenu } from '@/components/layout/user-menu';
import { cn } from '@/lib/utils';

interface AppSidebarProps {
    collapsed?: boolean;
    onToggle: () => void;
}

export function AppSidebar({ collapsed, onToggle }: AppSidebarProps) {
    const { url, props } = usePage();
    const [query, setQuery] = useState('');
    const access = props.nav;
    const current = activeHref(url);

    const sections = useMemo(() => {
        const keyword = query.trim().toLowerCase();

        return navigation
            .map((section) => ({
                ...section,
                items: section.items.filter(
                    (item) => isAllowed(item, access) && item.title.toLowerCase().includes(keyword),
                ),
            }))
            .filter((section) => section.items.length > 0);
    }, [access, query]);

    return (
        <aside className="bg-card flex h-full w-full flex-col border-r overflow-hidden transition-all duration-300">
            <div className={cn("flex items-center pt-5 pb-4", collapsed ? "flex-col gap-4 px-2" : "justify-between px-5")}>
                <Link href="/" className={cn("flex items-center gap-2", collapsed && "justify-center")}>
                    <span className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-lg text-sm font-bold shrink-0">
                        DS
                    </span>
                    {!collapsed && (
                        <span className="leading-tight">
                            <span className="block text-sm font-bold tracking-wide">DEVSCORE</span>
                            <span className="text-muted-foreground block text-[11px]">KPI IT Development</span>
                        </span>
                    )}
                </Link>
                <button
                    type="button"
                    onClick={onToggle}
                    className={cn("text-muted-foreground hover:bg-accent rounded-md p-1.5", collapsed && "border bg-slate-50/50 shadow-sm")}
                    aria-label="Tutup sidebar"
                >
                    {collapsed ? <PanelRightClose className="size-4" /> : <PanelLeftClose className="size-5" />}
                </button>
            </div>

            {!collapsed ? (
                <div className="border-b px-5 pb-5">
                    <label className="relative block">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Cari menu"
                            className="bg-card placeholder:text-muted-foreground focus-visible:ring-ring h-10 w-full rounded-lg border pr-3 pl-9 text-sm outline-none focus-visible:ring-2"
                        />
                    </label>
                </div>
            ) : (
                <div className="border-b pb-5 flex justify-center">
                    <button className="text-muted-foreground hover:text-primary rounded-md p-2 hover:bg-slate-50" title="Cari menu">
                        <Search className="size-5" />
                    </button>
                </div>
            )}

            <nav className={cn("flex-1 space-y-6 overflow-y-auto py-5", collapsed ? "px-2 scrollbar-none" : "px-3")}>
                {sections.map((section) => (
                    <div key={section.title} className={cn(collapsed && "flex flex-col items-center")}>
                        <p className={cn(
                            "text-muted-foreground font-semibold uppercase tracking-wider mb-2", 
                            collapsed ? "text-[10px] text-center" : "text-xs px-3"
                        )}>
                            {section.title}
                        </p>
                        <ul className="space-y-1 w-full">
                            {section.items.map((item) => (
                                <li key={item.href} className="w-full">
                                    <SidebarLink
                                        item={item}
                                        active={current === item.href}
                                        count={badgeCount(item, access)}
                                        collapsed={collapsed}
                                    />
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </nav>

            <UserMenu collapsed={collapsed} />
        </aside>
    );
}

function SidebarLink({ item, active, count, collapsed }: { item: NavItem; active: boolean; count?: number | null; collapsed?: boolean }) {
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            aria-current={active ? 'page' : undefined}
            title={collapsed ? item.title : undefined}
            className={cn(
                'flex items-center transition-colors group relative',
                collapsed ? 'justify-center h-10 w-10 mx-auto rounded-xl' : 'h-11 gap-3 rounded-lg px-3 text-[15px]',
                active
                    ? 'bg-primary text-primary-foreground font-semibold shadow-md'
                    : 'text-foreground/80 hover:bg-accent hover:text-foreground',
            )}
        >
            <Icon className="size-[18px]" />
            {!collapsed && <span className="flex-1">{item.title}</span>}
            
            {!!count && (
                <span
                    className={cn(
                        'inline-flex items-center justify-center font-semibold rounded-full',
                        collapsed 
                            ? 'absolute -top-1 -right-1 size-4 text-[9px] ring-2 ring-background border shadow-sm'
                            : 'min-w-6 px-1.5 text-[11px] leading-6',
                        active ? (collapsed ? 'bg-background text-primary' : 'bg-primary-foreground text-primary') : (collapsed ? 'bg-background text-foreground' : 'bg-muted text-foreground'),
                    )}
                >
                    {count > 99 ? '99+' : count}
                </span>
            )}
        </Link>
    );
}

function matches(path: string, href: string): boolean {
    return href === '/' ? path === '/' : path === href || path.startsWith(`${href}/`);
}

/** Href menu paling spesifik yang cocok dengan URL (mis. /items/available mengalahkan /items). */
function activeHref(currentUrl: string): string | null {
    const path = currentUrl.split('?')[0];

    return (
        navigation
            .flatMap((section) => section.items.map((item) => item.href))
            .filter((href) => matches(path, href))
            .sort((a, b) => b.length - a.length)[0] ?? null
    );
}
