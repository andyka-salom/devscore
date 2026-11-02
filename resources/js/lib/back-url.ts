/**
 * Navigasi "kembali" ke daftar asal (beserta halaman, filter, dan sort-nya).
 * Daftar menambahkan `?back=<url daftar>` saat membuka detail; detail membaca nilainya.
 */

const BACK_PARAM = 'back';

/** URL detail dengan URL halaman saat ini sebagai tujuan kembali. */
export function withBack(url: string): string {
    if (typeof window === 'undefined') return url;

    const target = new URL(url, window.location.origin);
    target.searchParams.set(BACK_PARAM, window.location.pathname + window.location.search);

    return target.pathname + target.search;
}

/**
 * Tujuan kembali dari URL halaman Inertia (`usePage().url`), atau $fallback.
 * Hanya path relatif di aplikasi ini yang diterima (bukan `//host` atau URL penuh).
 */
export function backUrlFrom(pageUrl: string, fallback: string): string {
    const query = pageUrl.includes('?') ? pageUrl.slice(pageUrl.indexOf('?') + 1) : '';
    const back = new URLSearchParams(query).get(BACK_PARAM);

    return back !== null && back.startsWith('/') && !back.startsWith('//') ? back : fallback;
}
