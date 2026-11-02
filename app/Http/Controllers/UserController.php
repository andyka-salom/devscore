<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\SaveUser;
use App\Enums\Role;
use App\Http\Requests\Users\SaveUserRequest;
use App\Http\Resources\FormOptions;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $role = $request->enum('role', Role::class);

        $users = User::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->whereLike('name', "%{$search}%", caseSensitive: false)
                ->orWhereLike('email', "%{$search}%", caseSensitive: false)))
            ->when($role !== null, fn (Builder $query) => $query->where('role', $role))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => UserResource::collection($users),
            'filters' => ['search' => $search === '' ? null : $search, 'role' => $role?->value],
            'options' => ['roles' => FormOptions::enum(Role::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/form', [
            'user' => null,
            'options' => ['roles' => FormOptions::enum(Role::class)],
        ]);
    }

    public function store(SaveUserRequest $request, SaveUser $save): RedirectResponse
    {
        $user = $save->handle(null, $request->user(), $request->userData());

        Inertia::flash('success', "User {$user->name} berhasil dibuat.");

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'is_self' => $user->id === $request->user()->id,
            ],
            'options' => ['roles' => FormOptions::enum(Role::class)],
        ]);
    }

    public function update(SaveUserRequest $request, User $user, SaveUser $save): RedirectResponse
    {
        $save->handle($user, $request->user(), $request->userData());

        Inertia::flash('success', "User {$user->name} berhasil diperbarui.");

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user, DeleteUser $delete): RedirectResponse
    {
        try {
            $delete->handle($user, $request->user());
        } catch (ValidationException $exception) {
            Inertia::flash('error', $exception->validator->errors()->first());

            return back();
        }

        Inertia::flash('success', "User {$user->name} berhasil dihapus.");

        return to_route('users.index');
    }
}
