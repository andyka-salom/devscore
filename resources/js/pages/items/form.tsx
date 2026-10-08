import { Link, useForm } from '@inertiajs/react';
import { ImageIcon, X } from 'lucide-react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { ItemFormData, ItemFormPageProps } from '@/types/item';

export default function ItemForm({ item, options, defaults }: ItemFormPageProps) {
    const isEdit = item !== null;
    const form = useForm<ItemFormData & { _method?: string }>({
        project_id: item?.project_id ?? defaults.project_id ?? '',
        type: item?.type ?? 'task',
        title: item?.title ?? '',
        description: item?.description ?? '',
        steps_to_reproduce: item?.steps_to_reproduce ?? '',
        priority: item?.priority ?? 'medium',
        due_date: item?.due_date ?? '',
        image: null,
        remove_image: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.transform((data) => ({
                ...data,
                _method: 'put',
            }));
            form.post(`/items/${item.id}`);
        } else {
            form.post('/items');
        }
    };

    const cancelHref = isEdit ? `/items/${item.id}` : '/items';

    return (
        <AppLayout title={isEdit ? `Edit ${item.code}` : 'Buat Item'} backUrl={cancelHref}>
            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <div>
                        <CardTitle>{isEdit ? `Edit ${item.code}` : 'Buat Item Baru'}</CardTitle>
                        <CardDescription>
                            Item baru masuk Backlog. Manager akan men-triage (difficulty & estimasi) sebelum item bisa
                            di-claim programmer.
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
                                    disabled={isEdit}
                                    aria-invalid={!!form.errors.project_id}
                                />
                            </FormField>
                            <FormField label="Tipe" htmlFor="type" error={form.errors.type} required>
                                <Select
                                    id="type"
                                    options={options.types}
                                    value={form.data.type}
                                    onValueChange={(value) => value && form.setData('type', value)}
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

                        <FormField label="Deskripsi" htmlFor="description" error={form.errors.description}>
                            <Textarea
                                id="description"
                                rows={6}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                        </FormField>

                        {form.data.type === 'bug' && (
                            <FormField
                                label="Detail Bug"
                                htmlFor="steps"
                                error={form.errors.steps_to_reproduce}
                                hint="Tuliskan keterangan detail error atau skenario untuk menemukan bug tersebut."
                            >
                                <Textarea
                                    id="steps"
                                    rows={5}
                                    value={form.data.steps_to_reproduce}
                                    onChange={(event) => form.setData('steps_to_reproduce', event.target.value)}
                                />
                            </FormField>
                        )}

                        <FormField label="Foto / Image" htmlFor="image" error={form.errors.image} hint="Maks. 5MB">
                            {isEdit && item.image_url && !form.data.remove_image && (
                                <div className="mb-3 relative inline-block">
                                    <img src={item.image_url} alt="Lampiran" className="h-32 object-contain rounded-md border" />
                                    <button
                                        type="button"
                                        onClick={() => form.setData('remove_image', true)}
                                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow hover:bg-red-600"
                                        title="Hapus foto"
                                    >
                                        <X className="size-3" />
                                    </button>
                                </div>
                            )}
                            {form.data.remove_image && (
                                <div className="mb-3 text-sm text-amber-600 bg-amber-50 p-2 rounded-md border border-amber-100 flex items-center">
                                    <ImageIcon className="size-4 mr-2" />
                                    Foto saat ini akan dihapus saat disimpan.
                                </div>
                            )}
                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                onChange={(event) => form.setData('image', event.target.files?.[0] ?? null)}
                            />
                        </FormField>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField label="Prioritas" htmlFor="priority" error={form.errors.priority} required>
                                <Select
                                    id="priority"
                                    options={options.priorities}
                                    value={form.data.priority}
                                    onValueChange={(value) => value && form.setData('priority', value)}
                                />
                            </FormField>
                            <FormField label="Due date" htmlFor="due_date" error={form.errors.due_date}>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={form.data.due_date}
                                    onChange={(event) => form.setData('due_date', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <div className="flex justify-end gap-2 border-t pt-5">
                            <Link href={cancelHref} className={buttonVariants({ variant: 'outline' })}>
                                Batal
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                {isEdit ? 'Simpan Perubahan' : 'Buat Item'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
