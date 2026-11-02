<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Membuat atau memperbarui user (oleh superadmin: Manager/Administrator).
 */
final class SaveUser
{
    /**
     * @param  array{name: string, email: string, role: string, is_active: bool, password: string|null}  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(?User $user, User $actor, array $data): User
    {
        $user === null
            ? Gate::forUser($actor)->authorize('create', User::class)
            : Gate::forUser($actor)->authorize('update', $user);

        // Cegah admin mengunci dirinya sendiri dari menu manajemen user.
        if ($user !== null && $user->id === $actor->id) {
            if ($data['role'] !== $user->role->value) {
                throw ValidationException::withMessages(['role' => 'Anda tidak bisa mengubah role akun sendiri.']);
            }

            if (! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'Anda tidak bisa menonaktifkan akun sendiri.']);
            }
        }

        $user ??= new User;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);

        if ($data['password'] !== null) {
            $user->password = $data['password'];
        }

        $user->save();

        return $user;
    }
}
