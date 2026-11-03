import { router, useForm } from '@inertiajs/react';
import { ClipboardList, Pencil, Plus, Trash2, User } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog } from '@/components/ui/dialog';
import { Input, Textarea } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import type { TestCase, TestCaseResult } from '@/types/item';

const RESULT_TONE: Record<TestCaseResult, BadgeTone> = {
    untested: 'muted',
    passed: 'success',
    failed: 'danger',
};

interface TestCasePanelProps {
    itemId: number;
    testCases: TestCase[];
    canAdd: boolean;
}

interface ScenarioForm {
    scenario: string;
    steps: string;
    expected_result: string;
}

interface ResultForm {
    result: Exclude<TestCaseResult, 'untested'> | '';
    actual_result: string;
}

/**
 * Daftar test case item. "Dibuat oleh" dan "Dites oleh" diisi otomatis oleh backend
 * dari user yang menulis skenario dan yang mengisi hasilnya.
 */
export function TestCasePanel({ itemId, testCases, canAdd }: TestCasePanelProps) {
    const [editing, setEditing] = useState<TestCase | 'new' | null>(null);
    const [testing, setTesting] = useState<TestCase | null>(null);

    const scenarioForm = useForm<ScenarioForm>({ scenario: '', steps: '', expected_result: '' });
    const resultForm = useForm<ResultForm>({ result: '', actual_result: '' });

    const openScenario = (testCase: TestCase | 'new') => {
        scenarioForm.clearErrors();
        scenarioForm.setData(
            testCase === 'new'
                ? { scenario: '', steps: '', expected_result: '' }
                : { scenario: testCase.scenario, steps: testCase.steps ?? '', expected_result: testCase.expected_result },
        );
        setEditing(testCase);
    };

    const closeScenario = () => {
        setEditing(null);
        scenarioForm.reset();
        scenarioForm.clearErrors();
    };

    const submitScenario = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeScenario };

        if (editing === 'new') {
            scenarioForm.post(`/items/${itemId}/test-cases`, options);
        } else if (editing) {
            scenarioForm.put(`/test-cases/${editing.id}`, options);
        }
    };

    const openResult = (testCase: TestCase) => {
        resultForm.clearErrors();
        resultForm.setData({
            result: testCase.result.value === 'untested' ? '' : testCase.result.value,
            actual_result: testCase.actual_result ?? '',
        });
        setTesting(testCase);
    };

    const closeResult = () => {
        setTesting(null);
        resultForm.reset();
        resultForm.clearErrors();
    };

    const submitResult = (event: FormEvent) => {
        event.preventDefault();
        if (testing) resultForm.put(`/test-cases/${testing.id}/result`, { preserveScroll: true, onSuccess: closeResult });
    };

    const count = (result: TestCaseResult) => testCases.filter((testCase) => testCase.result.value === result).length;

    return (
        <Card className="border-slate-200/60 shadow-sm">
            <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 pb-4">
                <div className="space-y-1">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <ClipboardList className="text-muted-foreground size-5" />
                        Test Case
                    </CardTitle>
                    {testCases.length > 0 && (
                        <p className="text-muted-foreground text-xs">
                            {count('passed')} lulus · {count('failed')} gagal · {count('untested')} belum dites
                        </p>
                    )}
                </div>
                {canAdd && (
                    <Button size="sm" variant="outline" onClick={() => openScenario('new')}>
                        <Plus /> Tambah Test Case
                    </Button>
                )}
            </CardHeader>
            <CardContent className="space-y-4 bg-white p-6">
                {testCases.length === 0 ? (
                    <p className="text-muted-foreground text-sm italic">Belum ada test case.</p>
                ) : (
                    testCases.map((testCase, index) => (
                        <TestCaseRow
                            key={testCase.id}
                            number={index + 1}
                            testCase={testCase}
                            onEdit={() => openScenario(testCase)}
                            onTest={() => openResult(testCase)}
                        />
                    ))
                )}
            </CardContent>

            <Dialog
                open={editing !== null}
                onClose={closeScenario}
                title={editing === 'new' ? 'Tambah Test Case' : 'Edit Test Case'}
                description="Anda akan tercatat sebagai pembuat test case ini."
                className="max-w-lg"
            >
                <form onSubmit={submitScenario} className="space-y-4">
                    <FormField label="Skenario" htmlFor="scenario" error={scenarioForm.errors.scenario} required>
                        <Input
                            id="scenario"
                            value={scenarioForm.data.scenario}
                            onChange={(event) => scenarioForm.setData('scenario', event.target.value)}
                            placeholder="mis. Login dengan password salah"
                            autoFocus
                        />
                    </FormField>
                    <FormField label="Langkah pengujian" htmlFor="steps" error={scenarioForm.errors.steps}>
                        <Textarea
                            id="steps"
                            rows={4}
                            value={scenarioForm.data.steps}
                            onChange={(event) => scenarioForm.setData('steps', event.target.value)}
                            placeholder={'1. Buka halaman login\n2. Isi password salah\n3. Klik Masuk'}
                        />
                    </FormField>
                    <FormField
                        label="Expected result"
                        htmlFor="expected_result"
                        error={scenarioForm.errors.expected_result}
                        required
                    >
                        <Textarea
                            id="expected_result"
                            rows={3}
                            value={scenarioForm.data.expected_result}
                            onChange={(event) => scenarioForm.setData('expected_result', event.target.value)}
                            placeholder="Muncul pesan error dan user tetap di halaman login"
                        />
                    </FormField>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={closeScenario}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={scenarioForm.processing}>
                            Simpan
                        </Button>
                    </div>
                </form>
            </Dialog>

            <Dialog
                open={testing !== null}
                onClose={closeResult}
                title={testing ? `Hasil: ${testing.scenario}` : ''}
                description="Anda akan tercatat sebagai penguji test case ini."
                className="max-w-lg"
            >
                {testing && (
                    <form onSubmit={submitResult} className="space-y-4">
                        <div className="rounded-md border bg-slate-50 p-3 text-sm">
                            <p className="text-muted-foreground mb-1 text-xs font-semibold uppercase">Expected result</p>
                            <p className="whitespace-pre-line">{testing.expected_result}</p>
                        </div>
                        <FormField label="Hasil" error={resultForm.errors.result} required>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant={resultForm.data.result === 'passed' ? 'default' : 'outline'}
                                    onClick={() => resultForm.setData('result', 'passed')}
                                    aria-pressed={resultForm.data.result === 'passed'}
                                >
                                    Lulus
                                </Button>
                                <Button
                                    type="button"
                                    variant={resultForm.data.result === 'failed' ? 'destructive' : 'outline'}
                                    onClick={() => resultForm.setData('result', 'failed')}
                                    aria-pressed={resultForm.data.result === 'failed'}
                                >
                                    Gagal
                                </Button>
                            </div>
                        </FormField>
                        <FormField
                            label="Hasil aktual"
                            htmlFor="actual_result"
                            error={resultForm.errors.actual_result}
                            hint="Wajib diisi bila test gagal."
                            required={resultForm.data.result === 'failed'}
                        >
                            <Textarea
                                id="actual_result"
                                rows={3}
                                value={resultForm.data.actual_result}
                                onChange={(event) => resultForm.setData('actual_result', event.target.value)}
                            />
                        </FormField>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={closeResult}>
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    resultForm.processing ||
                                    resultForm.data.result === '' ||
                                    (resultForm.data.result === 'failed' && resultForm.data.actual_result.trim() === '')
                                }
                            >
                                Simpan Hasil
                            </Button>
                        </div>
                    </form>
                )}
            </Dialog>
        </Card>
    );
}

function TestCaseRow({
    number,
    testCase,
    onEdit,
    onTest,
}: {
    number: number;
    testCase: TestCase;
    onEdit: () => void;
    onTest: () => void;
}) {
    return (
        <div className="rounded-xl border border-slate-100 p-4 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex items-start gap-2">
                    <span className="text-muted-foreground mt-0.5 text-xs font-bold">#{number}</span>
                    <h4 className="font-semibold text-slate-900">{testCase.scenario}</h4>
                </div>
                <div className="flex items-center gap-1">
                    <Badge tone={RESULT_TONE[testCase.result.value]}>{testCase.result.label}</Badge>
                    {testCase.can.update && (
                        <Button variant="ghost" size="icon" aria-label="Edit test case" onClick={onEdit}>
                            <Pencil className="size-4" />
                        </Button>
                    )}
                    {testCase.can.delete && (
                        <ConfirmDialog
                            title="Hapus test case?"
                            description={`Test case "${testCase.scenario}" akan dihapus permanen.`}
                            confirmLabel="Hapus"
                            variant="destructive"
                            onConfirm={() => router.delete(`/test-cases/${testCase.id}`, { preserveScroll: true })}
                            trigger={(open) => (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Hapus test case"
                                    onClick={open}
                                    className="text-red-500 hover:text-red-600"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            )}
                        />
                    )}
                </div>
            </div>

            <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                {testCase.steps && <Field label="Langkah" body={testCase.steps} className="sm:col-span-2" />}
                <Field label="Expected result" body={testCase.expected_result} />
                <Field label="Hasil aktual" body={testCase.actual_result} />
            </div>

            <div className="text-muted-foreground mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs">
                <div className="flex flex-wrap gap-x-4 gap-y-1">
                    <span className="flex items-center gap-1">
                        <User className="size-3.5" /> Dibuat oleh <b className="text-slate-700">{testCase.creator.name}</b>
                    </span>
                    <span className="flex items-center gap-1">
                        <User className="size-3.5" /> Dites oleh{' '}
                        {testCase.tester ? (
                            <>
                                <b className="text-slate-700">{testCase.tester.name}</b> · {formatDateTime(testCase.tested_at)}
                            </>
                        ) : (
                            '—'
                        )}
                    </span>
                </div>
                {testCase.can.record_result && (
                    <Button size="sm" variant="outline" onClick={onTest}>
                        {testCase.result.value === 'untested' ? 'Isi Hasil' : 'Ubah Hasil'}
                    </Button>
                )}
            </div>
        </div>
    );
}

function Field({ label, body, className }: { label: string; body: string | null; className?: string }) {
    return (
        <div className={className}>
            <p className="text-muted-foreground mb-1 text-xs font-semibold uppercase">{label}</p>
            {body ? (
                <p className="whitespace-pre-line text-slate-700">{body}</p>
            ) : (
                <p className="text-sm text-slate-400 italic">—</p>
            )}
        </div>
    );
}
