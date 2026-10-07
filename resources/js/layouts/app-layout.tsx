import { Head } from '@inertiajs/react';
import { Menu, PanelLeftOpen } from 'lucide-react';
import { useState, type ReactNode } from 'react';

import { AppSidebar } from '@/components/layout/app-sidebar';
import { FlashMessage } from '@/components/layout/flash-message';
import { cn } from '@/lib/utils';

const SIDEBAR_KEY = 'devscore.sidebar-collapsed';

interface AppLayoutProps {
    title: string;
    subtitle?: string;
    actions?: ReactNode;
    children: ReactNode;
}

function readCollapsed(): boolean {
    try {
        return window.localStorage.getItem(SIDEBAR_KEY) === '1';
    } catch {
        return false;
    }
}

export default function AppLayout({
    title,
    subtitle = 'Sistem KPI Divisi IT Development',
    actions,
    children,
}: AppLayoutProps) {
    const [collapsed, setCollapsed] = useState(readCollapsed);
    const [mobileOpen, setMobileOpen] = useState(false);

    const toggleCollapsed = (value: boolean) => {
        setCollapsed(value);
        try {
            window.localStorage.setItem(SIDEBAR_KEY, value ? '1' : '0');
        } catch {
            // penyimpanan lokal tidak tersedia; abaikan
        }
    };

    return (
        <div className="flex min-h-screen">
            <Head title={title} />

            {mobileOpen && (
                <div className="fixed inset-0 z-30 bg-black/40 lg:hidden" onClick={() => setMobileOpen(false)} />
            )}
            <div
                className={cn(
                    'fixed inset-y-0 left-0 z-40 transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen',
                    mobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                    collapsed ? 'lg:w-[72px]' : 'lg:w-64',
                )}
            >
                <AppSidebar
                    collapsed={collapsed}
                    onToggle={() => {
                        setMobileOpen(false);
                        toggleCollapsed(!collapsed);
                    }}
                />
            </div>

            <div className="flex min-w-0 flex-1 flex-col transition-all duration-300">
                <header className="bg-card sticky top-0 z-20 flex h-16 items-center gap-3 border-b px-4 shadow-xs sm:px-8">
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="text-muted-foreground hover:bg-accent rounded-md p-1.5 lg:hidden"
                        aria-label="Buka menu"
                    >
                        <Menu className="size-5" />
                    </button>

                    <div className="min-w-0 flex-1">
                        <h1 className="truncate text-base font-semibold">{title}</h1>
                        <p className="text-muted-foreground truncate text-xs">{subtitle}</p>
                    </div>
                    {actions}
                </header>

                <main className="flex-1 px-4 py-8 sm:px-8 lg:py-16">
                    <div className="mx-auto w-full max-w-6xl">{children}</div>
                </main>
            </div>

            <FlashMessage />
        </div>
    );
}
