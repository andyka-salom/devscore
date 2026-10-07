<?php

declare(strict_types=1);

use App\Models\User;

it('menampilkan halaman login', function (): void {
    $this->get('/login')->assertOk();
});

it('login dengan kredensial benar', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('menolak password salah', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('menolak user nonaktif', function (): void {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logout', function (): void {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
