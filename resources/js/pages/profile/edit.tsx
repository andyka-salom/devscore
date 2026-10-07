import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';

export default function EditProfile() {
    const { auth } = usePage().props;
    const user = auth.user;

    const profileForm = useForm({
        email: user.email,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updateProfile = (e: FormEvent) => {
        e.preventDefault();
        profileForm.patch('/profile', {
            preserveScroll: true,
        });
    };

    const updatePassword = (e: FormEvent) => {
        e.preventDefault();
        passwordForm.put('/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
            onError: (errors) => {
                if (errors.password) {
                    passwordForm.reset('password', 'password_confirmation');
                }
                if (errors.current_password) {
                    passwordForm.reset('current_password');
                }
            },
        });
    };

    return (
        <AppLayout title="Profil Saya" subtitle="Kelola informasi akun dan pengaturan keamanan.">
            <div className="space-y-6 max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Informasi Akun</CardTitle>
                        <CardDescription>Perbarui alamat email akun Anda.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={updateProfile} className="space-y-4">
                            <FormField label="Nama Lengkap">
                                <Input value={user.name} disabled />
                                <p className="text-xs text-muted-foreground mt-1">Nama tidak dapat diubah sendiri.</p>
                            </FormField>
                            
                            <FormField label="Peran">
                                <Input value={user.role?.label || 'N/A'} disabled />
                            </FormField>

                            <FormField label="Email" error={profileForm.errors.email}>
                                <Input
                                    type="email"
                                    value={profileForm.data.email}
                                    onChange={(e) => profileForm.setData('email', e.target.value)}
                                    required
                                />
                            </FormField>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={profileForm.processing}>
                                    Simpan Email
                                </Button>
                                {profileForm.recentlySuccessful && (
                                    <span className="text-sm text-green-600 font-medium">Tersimpan.</span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Ubah Password</CardTitle>
                        <CardDescription>Pastikan akun Anda menggunakan password yang panjang dan acak untuk tetap aman.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={updatePassword} className="space-y-4">
                            <FormField label="Password Saat Ini" error={passwordForm.errors.current_password}>
                                <Input
                                    type="password"
                                    value={passwordForm.data.current_password}
                                    onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                    required
                                />
                            </FormField>

                            <FormField label="Password Baru" error={passwordForm.errors.password}>
                                <Input
                                    type="password"
                                    value={passwordForm.data.password}
                                    onChange={(e) => passwordForm.setData('password', e.target.value)}
                                    required
                                />
                            </FormField>

                            <FormField label="Konfirmasi Password Baru" error={passwordForm.errors.password_confirmation}>
                                <Input
                                    type="password"
                                    value={passwordForm.data.password_confirmation}
                                    onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                    required
                                />
                            </FormField>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={passwordForm.processing}>
                                    Ganti Password
                                </Button>
                                {passwordForm.recentlySuccessful && (
                                    <span className="text-sm text-green-600 font-medium">Tersimpan.</span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
