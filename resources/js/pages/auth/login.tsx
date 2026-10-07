import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input, InputError, Label } from '@/components/ui/input';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-4">
            <Head title="Masuk" />
            <Card className="w-full max-w-sm p-6">
                <div className="mb-6 flex items-center gap-3">
                    <span className="bg-primary text-primary-foreground flex size-10 items-center justify-center rounded-lg font-bold">
                        DS
                    </span>
                    <div>
                        <h1 className="text-lg font-semibold">Masuk ke DevScore</h1>
                        <p className="text-muted-foreground text-sm">Sistem KPI Divisi IT Development</p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="username"
                            autoFocus
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            aria-invalid={!!form.errors.email}
                        />
                        <InputError message={form.errors.email} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            aria-invalid={!!form.errors.password}
                        />
                        <InputError message={form.errors.password} />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(event) => form.setData('remember', event.target.checked)}
                            className="size-4 rounded border"
                        />
                        Ingat saya
                    </label>
                    <Button type="submit" className="w-full" disabled={form.processing}>
                        {form.processing ? 'Memproses…' : 'Masuk'}
                    </Button>
                </form>
            </Card>
        </div>
    );
}
