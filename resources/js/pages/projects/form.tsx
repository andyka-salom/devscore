import { Link, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, InputError, Textarea } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { SelectOption } from '@/types';
import type { ProjectFormData, ProjectFormPageProps } from '@/types/project';

export default function ProjectForm({ project, options }: ProjectFormPageProps) {
    const isEdit = project !== null;
    const form = useForm<ProjectFormData>({
        code: project?.code ?? '',
        name: project?.name ?? '',
        description: project?.description ?? '',
        status: project?.status ?? 'planning',
        start_date: project?.start_date ?? '',
        end_date: project?.end_date ?? '',
        member_ids: project?.member_ids ?? [],
        links: project?.links ?? [],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(`/projects/${project.id}`);
        } else {
            form.post('/projects');
        }
    };

    const toggleMember = (id: number, checked: boolean) =>
        form.setData(
            'member_ids',
            checked ? [...form.data.member_ids, id] : form.data.member_ids.filter((memberId) => memberId !== id),
        );

    const updateLink = (index: number, field: 'label' | 'url', value: string) =>
        form.setData(
            'links',
            form.data.links.map((link, i) => (i === index ? { ...link, [field]: value } : link)),
        );

    const errors = form.errors as Record<string, string | undefined>;

    return (
        <AppLayout title={isEdit ? `Edit ${project.code}` : 'Project Baru'}>
            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <div>
                        <CardTitle>{isEdit ? `Edit ${project.name}` : 'Project Baru'}</CardTitle>
                        <CardDescription>
                            Kode project menjadi prefix item (mis. ERP-42) dan tidak bisa diubah.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-5 sm:grid-cols-3">
                            <FormField label="Kode" htmlFor="code" error={form.errors.code} required>
                                <Input
                                    id="code"
                                    value={form.data.code}
                                    onChange={(event) => form.setData('code', event.target.value.toUpperCase())}
                                    disabled={isEdit}
                                    maxLength={10}
                                />
                            </FormField>
                            <FormField
                                label="Nama"
                                htmlFor="name"
                                error={form.errors.name}
                                required
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <FormField label="Deskripsi" htmlFor="description" error={form.errors.description}>
                            <Textarea
                                id="description"
                                rows={5}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                        </FormField>

                        <div className="grid gap-5 sm:grid-cols-3">
                            <FormField label="Status" htmlFor="status" error={form.errors.status} required>
                                <Select
                                    id="status"
                                    options={options.statuses}
                                    value={form.data.status}
                                    onValueChange={(value) => value && form.setData('status', value)}
                                />
                            </FormField>
                            <FormField label="Tanggal mulai" htmlFor="start_date" error={form.errors.start_date}>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(event) => form.setData('start_date', event.target.value)}
                                />
                            </FormField>
                            <FormField label="Target selesai" htmlFor="end_date" error={form.errors.end_date}>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(event) => form.setData('end_date', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium">Anggota</legend>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <MemberGroup
                                    title="Programmer"
                                    users={options.programmers}
                                    selected={form.data.member_ids}
                                    onToggle={toggleMember}
                                />
                                <MemberGroup
                                    title="QA"
                                    users={options.qas}
                                    selected={form.data.member_ids}
                                    onToggle={toggleMember}
                                />
                            </div>
                            <InputError message={form.errors.member_ids} />
                        </fieldset>

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium">Links</legend>
                            {form.data.links.map((link, index) => (
                                <div key={index} className="flex items-start gap-2">
                                    <div className="grid flex-1 gap-2 sm:grid-cols-[1fr_2fr]">
                                        <div>
                                            <Input
                                                placeholder="Label (mis. Repository)"
                                                aria-label="Label link"
                                                value={link.label}
                                                onChange={(event) => updateLink(index, 'label', event.target.value)}
                                            />
                                            <InputError message={errors[`links.${index}.label`]} />
                                        </div>
                                        <div>
                                            <Input
                                                placeholder="https://"
                                                aria-label="URL link"
                                                value={link.url}
                                                onChange={(event) => updateLink(index, 'url', event.target.value)}
                                            />
                                            <InputError message={errors[`links.${index}.url`]} />
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="mt-1"
                                        aria-label="Hapus link"
                                        onClick={() =>
                                            form.setData(
                                                'links',
                                                form.data.links.filter((_, i) => i !== index),
                                            )
                                        }
                                    >
                                        <X />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => form.setData('links', [...form.data.links, { label: '', url: '' }])}
                            >
                                <Plus /> Tambah link
                            </Button>
                        </fieldset>

                        <div className="flex justify-end gap-2 border-t pt-5">
                            <Link
                                href={isEdit ? `/projects/${project.id}` : '/projects'}
                                className={buttonVariants({ variant: 'outline' })}
                            >
                                Batal
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                {isEdit ? 'Simpan Perubahan' : 'Buat Project'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}

function MemberGroup({
    title,
    users,
    selected,
    onToggle,
}: {
    title: string;
    users: SelectOption<number>[];
    selected: number[];
    onToggle: (id: number, checked: boolean) => void;
}) {
    return (
        <div className="rounded-md border p-3">
            <p className="text-muted-foreground mb-2 text-xs font-semibold uppercase">{title}</p>
            {users.length === 0 ? (
                <p className="text-muted-foreground text-sm">Belum ada user.</p>
            ) : (
                <div className="space-y-2">
                    {users.map((user) => (
                        <Checkbox
                            key={user.value}
                            checked={selected.includes(user.value)}
                            onCheckedChange={(checked) => onToggle(user.value, checked)}
                            label={user.label}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
