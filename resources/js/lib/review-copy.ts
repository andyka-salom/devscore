import type { ReviewMode } from '@/components/review-dialog';

/** Kalimat dampak aksi review per jenis entitas (dipakai halaman daftar dan Need Approval). */
export function reviewDescription(entity: 'feature' | 'bug', mode: ReviewMode, title: string, projectName: string): string {
    const noun = entity === 'feature' ? 'task' : 'bug';

    switch (mode) {
        case 'approve':
            return `“${title}” akan otomatis dibuat sebagai ${noun} di Backlog ${projectName}.`;
        case 'reject':
            return `“${title}” akan ditolak dan tidak masuk ke ${noun}.`;
        case 'escalate':
            return `“${title}” diteruskan ke Chief untuk keputusan akhir. ${noun === 'task' ? 'Task' : 'Bug'} baru dibuat setelah Chief menyetujui.`;
    }
}
