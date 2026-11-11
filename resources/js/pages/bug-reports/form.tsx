import { Link, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect, useMemo, type ChangeEvent, type FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BugReportFormData, BugReportFormPageProps } from '@/types/bug-report';

export default function BugReportForm({ options, defaults, max_photos }: BugReportFormPageProps) {
    const form = useForm<BugReportFormData>({
        project_id: defaults.project_id ?? '',
        title: '',
        description: '',
        steps_to_reproduce: '',
        priority: 'medium',
        photos: [],
    });

    const previews = useMemo(() => form.data.photos.map((file) => URL.createObjectURL(file)), [form.data.photos]);

    useEffect(() => () => previews.forEach((url) => URL.revokeObjectURL(url)), [previews]);

    const addPhotos = (event: ChangeEvent<HTMLInputElement>) => {
        const picked = Array.from(event.target.files ?? []);
        form.setData('photos', [...form.data.photos, ...picked].slice(0, max_photos));
        event.target.value = '';
    };

    const removePhoto = (index: number) => {
        form.setData(
            'photos',
            form.data.photos.filter((_, position) => position !== index),
        );
    };

    const photoError =
        form.errors.photos ?? Object.entries(form.errors).find(([key]) => key.startsWith('photos.'))?.[1];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/bug-reports', { forceFormData: true });
    };

    return (
        <AppLayout title="Laporkan Bug" backUrl="/bug-reports">
            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <div>
                        <CardTitle>Laporkan Temuan Bug</CardTitle>
                        <CardDescription>
                            Laporan akan ditinjau Manager. Jika disetujui, otomatis masuk sebagai bug di Backlog project
                            yang dipilih.
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
                            <FormField label="Prioritas" htmlFor="priority" error={form.errors.priority} required>
                                <Select
                                    id="priority"
                                    options={options.priorities}
                                    value={form.data.priority}
                                    onValueChange={(value) => value && form.setData('priority', value)}
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
                            hint="Apa yang terjadi dan apa yang seharusnya terjadi."
                        >
                            <Textarea
                                id="description"
                                rows={4}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Langkah Reproduksi"
                            htmlFor="steps_to_reproduce"
                            error={form.errors.steps_to_reproduce}
                        >
                            <Textarea
                                id="steps_to_reproduce"
                                rows={4}
                                value={form.data.steps_to_reproduce}
                                onChange={(event) => form.setData('steps_to_reproduce', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Bukti Foto"
                            htmlFor="photos"
                            error={photoError}
                            hint={`Maks. ${max_photos} foto, masing-masing 5MB.`}
                        >
                            {previews.length > 0 && (
                                <div className="mb-3 flex flex-wrap gap-3">
                                    {previews.map((url, index) => (
                                        <div key={url} className="relative">
                                            <img
                                                src={url}
                                                alt={`Bukti foto ${index + 1}`}
                                                className="h-24 w-24 rounded-md border object-cover"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removePhoto(index)}
                                                className="absolute -top-2 -right-2 rounded-full bg-red-500 p-1 text-white shadow hover:bg-red-600"
                                                title="Hapus foto"
                                            >
                                                <X className="size-3" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <Input
                                id="photos"
                                type="file"
                                accept="image/*"
                                multiple
                                disabled={form.data.photos.length >= max_photos}
                                onChange={addPhotos}
                            />
                        </FormField>

                        <div className="flex justify-end gap-2 border-t pt-5">
                            <Link href="/bug-reports" className={buttonVariants({ variant: 'outline' })}>
                                Batal
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                Kirim Temuan
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
