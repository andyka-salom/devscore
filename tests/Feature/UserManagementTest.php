<?php

declare(strict_types=1);

use App\Actions\Users\DeleteUser;
use App\Enums\Role;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function admin(): User
{
    return User::factory()->admin()->create();
}

it('admin melihat daftar user dengan filter role & pencarian', function (): void {
    User::factory()->qa()->create(['name' => 'Rina QA']);
    User::factory()->programmer()->create(['name' => 'Budi Dev']);

    $this->actingAs(admin())->get('/users?role=qa&search=rina')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Rina QA')
            ->where('filters.role', 'qa'));
});

it('manager adalah superadmin dan boleh mengelola user', function (): void {
    $manager = manager();
    $target = User::factory()->programmer()->create();

    $this->actingAs($manager)->get('/users')->assertOk();
    $this->actingAs($manager)->post('/users', [
        'name' => 'Admin Baru',
        'email' => 'adminbaru@example.com',
        'role' => 'admin',
        'is_active' => true,
        'password' => 'rahasia-123',
        'password_confirmation' => 'rahasia-123',
    ])->assertSessionHasNoErrors();
    $this->actingAs($manager)->delete("/users/{$target->id}")->assertRedirect('/users');

    expect(User::query()->where('email', 'adminbaru@example.com')->exists())->toBeTrue()
        ->and(User::query()->whereKey($target->id)->exists())->toBeFalse();
});

it('manager tidak bisa mengubah role akun sendiri', function (): void {
    $manager = manager();

    $this->actingAs($manager)->put("/users/{$manager->id}", [
        'name' => $manager->name,
        'email' => $manager->email,
        'role' => 'programmer',
        'is_active' => true,
    ])->assertSessionHasErrors('role');

    expect($manager->refresh()->role)->toBe(Role::Manager);
});

it('selain manager & admin tidak boleh mengakses manajemen user', function (Role $role): void {
    $user = User::factory()->create(['role' => $role]);
    $target = User::factory()->create();

    $this->actingAs($user)->get('/users')->assertForbidden();
    $this->actingAs($user)->post('/users', [])->assertForbidden();
    $this->actingAs($user)->put("/users/{$target->id}", [])->assertForbidden();
    $this->actingAs($user)->delete("/users/{$target->id}")->assertForbidden();
})->with([Role::Programmer, Role::Qa, Role::Chief]);

it('admin nonaktif tidak boleh mengakses manajemen user', function (): void {
    $this->actingAs(User::factory()->admin()->inactive()->create())->get('/users')->assertForbidden();
});

it('admin membuat user baru', function (): void {
    $this->actingAs(admin())->post('/users', [
        'name' => 'Sari',
        'email' => 'SARI@example.com',
        'role' => 'qa',
        'is_active' => true,
        'password' => 'rahasia-123',
        'password_confirmation' => 'rahasia-123',
    ])->assertRedirect('/users')->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'sari@example.com')->sole();
    expect($user->role)->toBe(Role::Qa)
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('rahasia-123', $user->password))->toBeTrue();
});

it('memvalidasi input user baru', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->actingAs(admin())->post('/users', [
        'name' => '',
        'email' => 'ada@example.com',
        'role' => 'superman',
        'password' => 'rahasia-123',
        'password_confirmation' => 'beda',
    ])->assertSessionHasErrors(['name', 'email', 'role', 'password']);
});

it('admin memperbarui user tanpa mengubah password bila dikosongkan', function (): void {
    $user = User::factory()->programmer()->create();
    $oldHash = $user->password;

    $this->actingAs(admin())->put("/users/{$user->id}", [
        'name' => 'Nama Baru',
        'email' => $user->email,
        'role' => 'manager',
        'is_active' => false,
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect('/users')->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and($user->role)->toBe(Role::Manager)
        ->and($user->is_active)->toBeFalse()
        ->and($user->password)->toBe($oldHash);
});

it('admin mereset password user', function (): void {
    $user = User::factory()->create();

    $this->actingAs(admin())->put("/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role->value,
        'is_active' => true,
        'password' => 'password-baru-1',
        'password_confirmation' => 'password-baru-1',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('password-baru-1', $user->refresh()->password))->toBeTrue();
});

it('admin tidak bisa menurunkan role atau menonaktifkan akun sendiri', function (): void {
    $admin = admin();
    $payload = ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'is_active' => true];

    $this->actingAs($admin)->put("/users/{$admin->id}", [...$payload, 'role' => 'manager'])
        ->assertSessionHasErrors('role');
    $this->actingAs($admin)->put("/users/{$admin->id}", [...$payload, 'is_active' => false])
        ->assertSessionHasErrors('is_active');

    expect($admin->refresh()->role)->toBe(Role::Admin)->and($admin->is_active)->toBeTrue();
});

it('admin menghapus user tanpa riwayat', function (): void {
    $user = User::factory()->qa()->create();
    Project::factory()->create()->members()->attach($user);

    $this->actingAs(admin())->delete("/users/{$user->id}")->assertRedirect('/users');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('menolak menghapus user yang sudah punya riwayat kerja', function (): void {
    $item = Item::factory()->inProgress()->create();
    $programmer = assigneeOf($item);

    $this->actingAs(admin())->delete("/users/{$programmer->id}")
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.error');

    expect(User::query()->whereKey($programmer->id)->exists())->toBeTrue();
});

it('DeleteUser melempar validasi untuk user dengan riwayat dan menolak hapus diri sendiri', function (): void {
    $item = Item::factory()->inProgress()->create();
    $admin = admin();

    expect(fn () => app(DeleteUser::class)->handle(assigneeOf($item), $admin))
        ->toThrow(ValidationException::class);

    $this->actingAs($admin)->delete("/users/{$admin->id}")->assertForbidden();
});

it('menu manajemen user hanya muncul untuk manager & admin', function (): void {
    $this->actingAs(admin())->get('/')->assertInertia(fn (Assert $page) => $page->where('nav.users', true));
    $this->actingAs(manager())->get('/')->assertInertia(fn (Assert $page) => $page->where('nav.users', true));
    $this->actingAs(User::factory()->qa()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('nav.users', false));
});
