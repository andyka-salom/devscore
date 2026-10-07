export interface DateRange {
    start: string;
    end: string;
}

export interface DateRangePreset {
    id: string;
    label: string;
    range: () => DateRange;
}

/** Format Date lokal ke YYYY-MM-DD (tanpa konversi UTC). */
export function toIsoDate(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function monthRange(offset: number, length = 1): DateRange {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth() + offset - (length - 1), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + offset + 1, 0);

    return { start: toIsoDate(start), end: toIsoDate(end) };
}

export const DATE_RANGE_PRESETS: DateRangePreset[] = [
    { id: 'this-month', label: 'Bulan ini', range: () => monthRange(0) },
    { id: 'last-month', label: 'Bulan lalu', range: () => monthRange(-1) },
    { id: 'last-3-months', label: '3 bulan', range: () => monthRange(0, 3) },
    {
        id: 'this-year',
        label: 'Tahun ini',
        range: () => {
            const year = new Date().getFullYear();

            return { start: `${year}-01-01`, end: `${year}-12-31` };
        },
    },
];

export function presetFor(range: DateRange): string | null {
    return (
        DATE_RANGE_PRESETS.find((preset) => {
            const candidate = preset.range();

            return candidate.start === range.start && candidate.end === range.end;
        })?.id ?? null
    );
}
