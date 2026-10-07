import { useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import type { SelectOption } from '@/types';
import type { TriageState } from '@/types/item';

const DIFFICULTY_OPTIONS: SelectOption<number>[] = [
    { value: 1, label: '1 – Sangat mudah' },
    { value: 2, label: '2 – Mudah' },
    { value: 3, label: '3 – Sedang' },
    { value: 4, label: '4 – Sulit' },
    { value: 5, label: '5 – Sangat sulit' },
];

interface TriagePanelProps {
    itemId: number;
    triage: TriageState;
    canAssign: boolean;
}

interface TriageForm {
    difficulty: number | '';
    estimate_days: string;
    qa_id: number | '';
    assignee_id: number | '';
    reason: string;
}

/**
 * Panel triage Manager: difficulty, estimasi, QA, dan programmer (opsional — kosongkan agar bisa di-claim).
 */
export function TriagePanel({ itemId, triage, canAssign }: TriagePanelProps) {
    const form = useForm<TriageForm>({
        difficulty: triage.difficulty ?? '',
        estimate_days: triage.estimate_days === null ? '' : String(triage.estimate_days),
        qa_id: triage.qa_id ?? '',
        assignee_id: triage.assignee_id ?? '',
        reason: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/items/${itemId}/triage`, { preserveScroll: true, onSuccess: () => form.setData('reason', '') });
    };

    return (
        <Card>
            <CardHeader>
                <div>
                    <CardTitle className="text-base">Triage</CardTitle>
                    <CardDescription>
                        {triage.locked
                            ? 'Estimasi terkunci karena item sudah pernah dikerjakan. Perubahan wajib beralasan.'
                            : 'Kosongkan programmer agar item muncul di Task Tersedia untuk di-claim.'}
                    </CardDescription>
                </div>
                {triage.locked && <Lock className="text-muted-foreground size-4 shrink-0" />}
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <FormField label="Difficulty" htmlFor="difficulty" error={form.errors.difficulty} required>
                        <Select
                            id="difficulty"
                            options={DIFFICULTY_OPTIONS}
                            value={form.data.difficulty}
                            onValueChange={(value) => form.setData('difficulty', value ?? '')}
                            placeholder="Pilih difficulty"
                        />
                    </FormField>
                    <FormField
                        label="Estimasi (hari kerja)"
                        htmlFor="estimate_days"
                        error={form.errors.estimate_days}
                        hint="Kelipatan 0,5 hari"
                        required
                    >
                        <Input
                            id="estimate_days"
                            type="number"
                            step="0.5"
                            min="0.5"
                            value={form.data.estimate_days}
                            onChange={(event) => form.setData('estimate_days', event.target.value)}
                        />
                    </FormField>
                    <FormField label="QA" htmlFor="qa_id" error={form.errors.qa_id} required>
                        <Select
                            id="qa_id"
                            options={triage.qas}
                            value={form.data.qa_id}
                            onValueChange={(value) => form.setData('qa_id', value ?? '')}
                            placeholder="Pilih QA"
                            disabled={!canAssign}
                        />
                    </FormField>
                    <FormField label="Programmer" htmlFor="assignee_id" error={form.errors.assignee_id}>
                        <Select
                            id="assignee_id"
                            options={triage.programmers}
                            value={form.data.assignee_id}
                            onValueChange={(value) => form.setData('assignee_id', value ?? '')}
                            placeholder="— Biarkan di-claim —"
                            disabled={!canAssign}
                        />
                    </FormField>
                    {triage.locked && (
                        <FormField label="Alasan perubahan" htmlFor="triage_reason" error={form.errors.reason} required>
                            <Textarea
                                id="triage_reason"
                                rows={2}
                                value={form.data.reason}
                                onChange={(event) => form.setData('reason', event.target.value)}
                            />
                        </FormField>
                    )}
                    <Button type="submit" className="w-full" disabled={form.processing || !form.isDirty}>
                        Simpan Triage
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
