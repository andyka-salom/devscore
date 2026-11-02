import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, InputError } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { UserFormData, UserFormPageProps } from '@/types/user';

export default function UserForm({ user, options }: UserFormPageProps) {
    const isEdit = user !== null;
    const isSelf = user?.is_self ?? false;
    const form = useForm<UserFormData>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        role: user?.role ?? 'programmer',
        is_active: user?.is_active ?? true,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(`/users/${user.id}`);
        } else {
            form.post('/users');
        }
    };

    return (
        <AppLayout title={isEdit ? `Edit ${user.name}` : 'User Baru'} backUrl="/users">
            <Card className="mx-auto max-w-2xl">
                <CardHeader>
                    <div>
                        <CardTitle>{isEdit ? `Edit ${user.name}` : 'User Baru'}</CardTitle>
                        <CardDescription>
                            {isEdit
                                ? 'Kosongkan password bila tidak ingin mengubahnya.'
                                : 'Satu user hanya punya satu role.'}
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField label="Nama" htmlFor="name" error={form.errors.name} required>
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                />
                            </FormField>
                            <FormField label="Email" htmlFor="email" error={form.errors.email} required>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <FormField
                            label="Role"
                            htmlFor="role"
                            error={form.errors.role}
                            hint={isSelf ? 'Role akun sendiri tidak bisa diubah.' : undefined}
                            required
                        >
                            <Select
                                id="role"
                                options={options.roles}
                                value={form.data.role}
                                disabled={isSelf}
                                onValueChange={(value) => value && form.setData('role', value)}
                            />
                        </FormField>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                label="Password"
                                htmlFor="password"
                                error={form.errors.password}
                                required={!isEdit}
                            >
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="new-password"
                                    value={form.data.password}
                                    onChange={(event) => form.setData('password', event.target.value)}
                                />
                            </FormField>
                            <FormField
                                label="Konfirmasi password"
                                htmlFor="password_confirmation"
                                error={form.errors.password_confirmation}
                                required={!isEdit}
                            >
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    value={form.data.password_confirmation}
                                    onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <div>
                            <Checkbox
                                checked={form.data.is_active}
                                disabled={isSelf}
                                onCheckedChange={(checked) => form.setData('is_active', checked)}
                                label="Aktif"
                                description="User nonaktif tidak bisa login dan tidak muncul di pilihan anggota/assignee."
                            />
                            <InputError message={form.errors.is_active} />
                        </div>

                        <div className="flex justify-end gap-2 border-t pt-5">
                            <Link href="/users" className={buttonVariants({ variant: 'outline' })}>
                                Batal
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                {isEdit ? 'Simpan Perubahan' : 'Buat User'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
