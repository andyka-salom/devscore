<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? $this->user()?->can('update', $user) === true
            : $this->user()?->can('create', User::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $isEdit = $user instanceof User;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($isEdit ? $user->id : null),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            // Saat edit, password kosong berarti tidak diubah.
            'password' => [$isEdit ? 'nullable' : 'required', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'role' => 'role',
            'is_active' => 'status aktif',
            'password' => 'password',
        ];
    }

    /**
     * @return array{name: string, email: string, role: string, is_active: bool, password: string|null}
     */
    public function userData(): array
    {
        $password = $this->string('password')->toString();

        return [
            'name' => $this->string('name')->toString(),
            'email' => strtolower($this->string('email')->toString()),
            'role' => $this->string('role')->toString(),
            'is_active' => $this->boolean('is_active'),
            'password' => $password === '' ? null : $password,
        ];
    }
}
