import { router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, InputError } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import type { KpiSettings, SettingsPageProps } from '@/types/settings';

const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
const DIFFICULTY_KEYS = ['1', '2', '3', '4', '5'] as const;

/** Bobot & toleransi disimpan sebagai pecahan (0.4) tetapi diedit sebagai persen (40). */
const toPercent = (value: number) => Math.round(value * 1000) / 10;
const fromPercent = (value: string) => (value === '' ? 0 : Number(value) / 100);

export default function Settings({ settings, holidays }: SettingsPageProps) {
    const form = useForm<KpiSettings>(settings);
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/settings', { preserveScroll: true });
    };

    const programmerTotal = Object.values(form.data.programmer_weights).reduce((a, b) => a + b, 0);
    const qaTotal = Object.values(form.data.qa_weights).reduce((a, b) => a + b, 0);

    return (
        <AppLayout title="Pengaturan" subtitle="KPI, workflow, dan kalender kerja">
            <form onSubmit={submit} className="space-y-6">
                <Section title="Poin per difficulty" description="Dipakai untuk produktivitas KPI dan progres project.">
                    <div className="grid grid-cols-5 gap-3">
                        {DIFFICULTY_KEYS.map((key) => (
                            <FormField key={key} label={`Level ${key}`} error={errors[`difficulty_points.${key}`]}>
                                <Input
                                    type="number"
                                    min={0}
                                    value={form.data.difficulty_points[key]}
                                    onChange={(event) =>
                                        form.setData('difficulty_points', {
                                            ...form.data.difficulty_points,
                                            [key]: Number(event.target.value),
                                        })
                                    }
                                />
                            </FormField>
                        ))}
                    </div>
                </Section>

                <Section title="KPI Programmer">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Target poin per bulan" error={errors.monthly_target_points}>
                            <Input
                                type="number"
                                min={1}
                                step="0.5"
                                value={form.data.monthly_target_points}
                                onChange={(event) => form.setData('monthly_target_points', Number(event.target.value))}
                            />
                        </FormField>
                        <FormField label="Toleransi ketepatan waktu (%)" error={errors.on_time_tolerance}>
                            <Input
                                type="number"
                                min={0}
                                max={100}
                                value={toPercent(form.data.on_time_tolerance)}
                                onChange={(event) => form.setData('on_time_tolerance', fromPercent(event.target.value))}
                            />
                        </FormField>
                    </div>
                    <WeightInputs
                        weights={form.data.programmer_weights}
                        labels={{ productivity: 'Produktivitas', timeliness: 'Tepat waktu', quality: 'Kualitas' }}
                        onChange={(weights) => form.setData('programmer_weights', weights)}
                        total={programmerTotal}
                        error={errors.programmer_weights}
                    />
                    <Checkbox
                        checked={form.data.weight_by_points}
                        onCheckedChange={(checked) => form.setData('weight_by_points', checked)}
                        label="Bobot ketepatan waktu & kualitas dengan poin item"
                        description="Item sulit lebih berpengaruh pada persentase."
                    />
                </Section>

                <Section title="KPI QA">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Target keputusan QA per bulan" error={errors.qa_monthly_throughput_target}>
                            <Input
                                type="number"
                                min={1}
                                value={form.data.qa_monthly_throughput_target}
                                onChange={(event) =>
                                    form.setData('qa_monthly_throughput_target', Number(event.target.value))
                                }
                            />
                        </FormField>
                        <FormField label="Target waktu review (jam kerja)" error={errors.qa_review_target_hours}>
                            <Input
                                type="number"
                                min={0.5}
                                step="0.5"
                                value={form.data.qa_review_target_hours}
                                onChange={(event) => form.setData('qa_review_target_hours', Number(event.target.value))}
                            />
                        </FormField>
                    </div>
                    <WeightInputs
                        weights={form.data.qa_weights}
                        labels={{ throughput: 'Throughput', speed: 'Kecepatan', accuracy: 'Akurasi' }}
                        onChange={(weights) => form.setData('qa_weights', weights)}
                        total={qaTotal}
                        error={errors.qa_weights}
                    />
                </Section>

                <Section title="Workflow">
                    <Checkbox
                        checked={form.data.self_assign_enabled}
                        onCheckedChange={(checked) => form.setData('self_assign_enabled', checked)}
                        label="Programmer boleh claim (self-assign) task yang sudah di-triage"
                    />
                    <FormField
                        label="Batas reopen (hari sejak selesai)"
                        error={errors.reopen_window_days}
                        className="sm:w-1/2"
                    >
                        <Input
                            type="number"
                            min={0}
                            value={form.data.reopen_window_days}
                            onChange={(event) => form.setData('reopen_window_days', Number(event.target.value))}
                        />
                    </FormField>
                </Section>

                <Section title="Kalender kerja" description="Dasar perhitungan durasi kerja aktual.">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Jam mulai (Senin-Jumat)" error={errors.work_start}>
                            <Input
                                type="time"
                                value={form.data.work_start}
                                onChange={(event) => form.setData('work_start', event.target.value)}
                            />
                        </FormField>
                        <FormField label="Jam selesai (Senin-Jumat)" error={errors.work_end}>
                            <Input
                                type="time"
                                value={form.data.work_end}
                                onChange={(event) => form.setData('work_end', event.target.value)}
                            />
                        </FormField>
                        
                        {form.data.work_days.includes(6) && (
                            <>
                                <FormField label="Jam mulai (Sabtu)" error={errors.work_start_saturday}>
                                    <Input
                                        type="time"
                                        value={form.data.work_start_saturday}
                                        onChange={(event) => form.setData('work_start_saturday', event.target.value)}
                                    />
                                </FormField>
                                <FormField label="Jam selesai (Sabtu)" error={errors.work_end_saturday}>
                                    <Input
                                        type="time"
                                        value={form.data.work_end_saturday}
                                        onChange={(event) => form.setData('work_end_saturday', event.target.value)}
                                    />
                                </FormField>
                            </>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-4">
                        {DAYS.map((day, index) => {
                            const iso = index + 1;

                            return (
                                <Checkbox
                                    key={day}
                                    label={day}
                                    checked={form.data.work_days.includes(iso)}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'work_days',
                                            checked
                                                ? [...form.data.work_days, iso].sort((a, b) => a - b)
                                                : form.data.work_days.filter((d) => d !== iso),
                                        )
                                    }
                                />
                            );
                        })}
                    </div>
                    <InputError message={errors.work_days} />
                </Section>

                <div className="flex justify-end">
                    <Button type="submit" disabled={form.processing || !form.isDirty}>
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>

            <div className="mt-6">
                <HolidaySection holidays={holidays} />
            </div>
        </AppLayout>
    );
}

function Section({ title, description, children }: { title: string; description?: string; children: ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <div>
                    <CardTitle className="text-base">{title}</CardTitle>
                    {description && <CardDescription>{description}</CardDescription>}
                </div>
            </CardHeader>
            <CardContent className="space-y-4">{children}</CardContent>
        </Card>
    );
}

function WeightInputs<K extends string>({
    weights,
    labels,
    onChange,
    total,
    error,
}: {
    weights: Record<K, number>;
    labels: Record<K, string>;
    onChange: (weights: Record<K, number>) => void;
    total: number;
    error?: string;
}) {
    const keys = Object.keys(labels) as K[];
    const valid = Math.abs(total - 1) < 0.001;

    return (
        <div className="space-y-2">
            <div className="grid gap-3 sm:grid-cols-3">
                {keys.map((key) => (
                    <FormField key={key} label={`Bobot ${labels[key]} (%)`}>
                        <Input
                            type="number"
                            min={0}
                            max={100}
                            value={toPercent(weights[key])}
                            onChange={(event) => onChange({ ...weights, [key]: fromPercent(event.target.value) })}
                        />
                    </FormField>
                ))}
            </div>
            <p className={valid ? 'text-muted-foreground text-xs' : 'text-destructive text-xs'}>
                Total bobot: {toPercent(total)}% {valid ? '' : '(harus 100%)'}
            </p>
            <InputError message={error} />
        </div>
    );
}

import { HolidayCalendar } from '@/components/holiday-calendar';

function HolidaySection({ holidays }: { holidays: SettingsPageProps['holidays'] }) {
    const form = useForm();

    const submitSync = () => {
        form.post('/settings/holidays/sync', { preserveScroll: true });
    };

    return (
        <Section title="Hari libur & cuti bersama" description="Tanggal ini tidak dihitung sebagai hari kerja.">
            <div className="flex justify-end mb-4">
                <Button 
                    type="button" 
                    variant="outline" 
                    onClick={submitSync}
                    disabled={form.processing}
                >
                    Sync dari Kalender Google
                </Button>
            </div>
            
            <HolidayCalendar holidays={holidays} />
        </Section>
    );
}
