import { useState } from 'react';

import { SegmentedTabs } from '@/components/segmented-tabs';
import { Button } from '@/components/ui/button';
import { Input, InputError } from '@/components/ui/input';
import { DATE_RANGE_PRESETS, presetFor, type DateRange } from '@/lib/date-range';

interface DateRangeFilterProps {
    value: DateRange;
    onChange: (range: DateRange) => void;
    error?: string;
}

/**
 * Filter rentang tanggal: preset cepat + input tanggal mulai/akhir bebas.
 */
export function DateRangeFilter({ value, onChange, error }: DateRangeFilterProps) {
    const [draft, setDraft] = useState<DateRange>(value);
    const activePreset = presetFor(value) ?? 'custom';
    const dirty = draft.start !== value.start || draft.end !== value.end;
    const invalid = draft.start === '' || draft.end === '' || draft.start > draft.end;

    return (
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
            <SegmentedTabs
                tabs={[
                    ...DATE_RANGE_PRESETS.map((preset) => ({ value: preset.id, label: preset.label })),
                    { value: 'custom', label: 'Kustom' },
                ]}
                value={activePreset}
                onChange={(id) => {
                    const preset = DATE_RANGE_PRESETS.find((candidate) => candidate.id === id);
                    if (!preset) return;

                    const range = preset.range();
                    setDraft(range);
                    onChange(range);
                }}
            />
            <form
                className="flex flex-wrap items-center gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    if (!invalid) onChange(draft);
                }}
            >
                <Input
                    type="date"
                    aria-label="Tanggal mulai"
                    className="h-9 w-40"
                    value={draft.start}
                    max={draft.end || undefined}
                    onChange={(event) => setDraft({ ...draft, start: event.target.value })}
                />
                <span className="text-muted-foreground text-sm">s/d</span>
                <Input
                    type="date"
                    aria-label="Tanggal akhir"
                    className="h-9 w-40"
                    value={draft.end}
                    min={draft.start || undefined}
                    onChange={(event) => setDraft({ ...draft, end: event.target.value })}
                />
                <Button type="submit" size="sm" disabled={!dirty || invalid}>
                    Terapkan
                </Button>
            </form>
            <InputError message={error} />
        </div>
    );
}
