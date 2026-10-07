import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LogOut } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { initials } from '@/lib/format';
import { cn } from '@/lib/utils';

export function UserMenu({ collapsed }: { collapsed?: boolean }) {
    const user = usePage().props.auth.user;
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;

        const close = (event: MouseEvent) => {
            if (!ref.current?.contains(event.target as Node)) setOpen(false);
        };
        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    if (!user) return null;

    return (
        <div ref={ref} className={cn("relative border-t", collapsed ? "p-3 flex justify-center" : "p-4")}>
            {open && (
                <div className={cn("bg-card absolute mb-2 rounded-lg border p-1 shadow-lg z-50 w-48", collapsed ? "left-full ml-2 bottom-0" : "right-4 bottom-full left-4")}>
                    <div className="border-b px-3 py-2">
                        <p className="truncate text-sm font-medium">{user.name}</p>
                        <p className="text-muted-foreground truncate text-xs">{user.email}</p>
                    </div>
                    <Link
                        href="/profile"
                        className="hover:bg-accent mt-1 flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm"
                    >
                        Profil Saya
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="hover:bg-accent mt-1 flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-600 hover:text-red-700"
                    >
                        <LogOut className="size-4" />
                        Keluar
                    </Link>
                </div>
            )}
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                className={cn("hover:bg-accent flex items-center rounded-lg text-left", collapsed ? "justify-center p-1" : "w-full gap-3 p-1")}
            >
                <span className="bg-primary text-primary-foreground relative flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
                    {initials(user.name)}
                    <span className="border-card absolute right-0 bottom-0 size-2.5 rounded-full border-2 bg-emerald-500" />
                </span>
                {!collapsed && (
                    <>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-semibold">{user.name}</span>
                            <span className="text-muted-foreground block truncate text-xs">{user.role.label}</span>
                        </span>
                        <ChevronDown
                            className={cn('text-muted-foreground size-4 transition-transform', open && 'rotate-180')}
                        />
                    </>
                )}
            </button>
        </div>
    );
}
