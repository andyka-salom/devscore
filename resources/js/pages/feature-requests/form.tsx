import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { FeatureRequestFormData, FeatureRequestFormPageProps } from '@/types/feature-request';

export default function FeatureRequestForm({ options, defaults }: FeatureRequestFormPageProps) {
    const form = useForm<FeatureRequestFormData>({
        project_id: defaults.project_id ?? '',
        kind: 'enhancement',
        title: '',
        description: '',
        priority: 'medium',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/feature-requests');
    };

    return (
        <AppLayout title="Ajukan Fitur" backUrl="/feature-requests">
            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <div>
                        <CardTitle>Ajukan Fitur Tambahan / Baru</CardTitle>
                        <CardDescription>
                            Permintaan akan ditinjau Manager. Jika disetujui, otomatis masuk sebagai task di Backlog
                            project yang dipilih.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField label="Project" htmlFor="project_id" error={form.errors.project_id} required>
                                <Select
                                    id="project_id"
                                    options={options.projects}
                                    value={form.data.project_id}
                                    onValueChange={(value) => form.setData('project_id', value ?? '')}
                                    placeholder="Pilih project"
                                    aria-invalid={!!form.errors.project_id}
                                />
                            </FormField>
                            <FormField label="Jenis" htmlFor="kind" error={form.errors.kind} required>
                                <Select
                                    id="kind"
                                    options={options.kinds}
                                    value={form.data.kind}
                                    onValueChange={(value) => value && form.setData('kind', value)}
                                />
                            </FormField>
                        </div>

                        <FormField label="Judul" htmlFor="title" error={form.errors.title} required>
                            <Input
                                id="title"
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                                aria-invalid={!!form.errors.title}
                            />
                        </FormField>

                        <FormField
                            label="Deskripsi"
                            htmlFor="description"
                            error={form.errors.description}
                            hint="Jelaskan kebutuhan, manfaat, dan hasil yang diharapkan."
                        >
                            <Textarea
                                id="description"
                                rows={6}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Prioritas" htmlFor="priority" error={form.errors.priority} required>
                            <Select
                                id="priority"
                                options={options.priorities}
                                value={form.data.priority}
                                onValueChange={(value) => value && form.setData('priority', value)}
                            />
                        </FormField>

                        <div className="flex justify-end gap-2 border-t pt-5">
                            <Link href="/feature-requests" className={buttonVariants({ variant: 'outline' })}>
                                Batal
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                Kirim Permintaan
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
