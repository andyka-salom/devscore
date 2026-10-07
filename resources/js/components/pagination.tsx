import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';

import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

/**
 * Navigasi halaman untuk hasil paginator Laravel. Tidak dirender bila hanya satu halaman.
 */
export function Pagination<T>({ paginator }: { paginator: Paginated<T> }) {
    const { meta, links } = paginator;

    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-col items-center justify-between gap-3 pt-4 sm:flex-row" aria-label="Paginasi">
            <p className="text-muted-foreground text-sm">
                Menampilkan {meta.from}–{meta.to} dari {meta.total} data
            </p>
            <div className="flex items-center gap-2">
                <PageLink href={links.prev} label="Sebelumnya">
                    <ChevronLeft />
                </PageLink>
                <span className="text-sm tabular-nums">
                    {meta.current_page} / {meta.last_page}
                </span>
                <PageLink href={links.next} label="Berikutnya">
                    <ChevronRight />
                </PageLink>
            </div>
        </nav>
    );
}

function PageLink({ href, label, children }: { href: string | null; label: string; children: ReactNode }) {
    const className = cn(buttonVariants({ variant: 'outline', size: 'icon' }));

    if (!href) {
        return (
            <span aria-disabled className={cn(className, 'pointer-events-none opacity-50')} aria-label={label}>
                {children}
            </span>
        );
    }

    return (
        <Link href={href} preserveScroll preserveState className={className} aria-label={label}>
            {children}
        </Link>
    );
}
