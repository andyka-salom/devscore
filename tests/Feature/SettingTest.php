<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Holiday;
use App\Models\User;
use App\Services\SettingRepository;

function validSettings(array $overrides = []): array
{
    return [
        'difficulty_points' => ['1' => 1, '2' => 2, '3' => 3, '4' => 5, '5' => 8],
        'reopen_window_days' => 30,
        'self_assign_enabled' => false,
        'on_time_tolerance' => 0.15,
        'monthly_target_points' => 25,
        'programmer_weights' => ['productivity' => 0.5, 'timeliness' => 0.25, 'quality' => 0.25],
        'weight_by_points' => true,
        'qa_monthly_throughput_target' => 30,
        'qa_review_target_hours' => 6,
        'qa_weights' => ['throughput' => 0.3, 'speed' => 0.3, 'accuracy' => 0.4],
        'work_start' => '09:00',
        'work_end' => '18:00',
        'work_days' => [1, 2, 3, 4, 5, 6],
        ...$overrides,
    ];
}

it('manager menyimpan pengaturan KPI', function (): void {
    $this->actingAs(manager())->put('/settings', validSettings())->assertSessionHasNoErrors();

    $settings = app(SettingRepository::class);
    expect($settings->float(SettingKey::MonthlyTargetPoints))->toBe(25.0)
        ->and($settings->bool(SettingKey::SelfAssignEnabled))->toBeFalse()
        ->and($settings->array(SettingKey::WorkDays))->toBe([1, 2, 3, 4, 5, 6]);
});

it('menolak bobot yang totalnya bukan 100%', function (): void {
    $this->actingAs(manager())
        ->put('/settings', validSettings(['programmer_weights' => ['productivity' => 0.5, 'timeliness' => 0.5, 'quality' => 0.5]]))
        ->assertSessionHasErrors('programmer_weights');
});

it('menolak jam selesai sebelum jam mulai', function (): void {
    $this->actingAs(manager())
        ->put('/settings', validSettings(['work_start' => '17:00', 'work_end' => '08:00']))
        ->assertSessionHasErrors('work_end');
});

it('admin boleh, programmer & QA tidak boleh mengelola pengaturan', function (): void {
    $this->actingAs(User::factory()->admin()->create())->get('/settings')->assertOk();
    $this->actingAs(User::factory()->programmer()->create())->get('/settings')->assertForbidden();
    $this->actingAs(User::factory()->qa()->create())->put('/settings', validSettings())->assertForbidden();
});

it('mengelola hari libur', function (): void {
    $manager = manager();

    $this->actingAs($manager)->post('/settings/holidays', ['date' => '2026-12-25', 'name' => 'Natal'])->assertRedirect();
    $this->actingAs($manager)->post('/settings/holidays', ['date' => '2026-12-25', 'name' => 'Duplikat'])
        ->assertSessionHasErrors('date');

    $holiday = Holiday::query()->sole();
    $this->actingAs($manager)->delete("/settings/holidays/{$holiday->id}")->assertRedirect();

    expect(Holiday::query()->count())->toBe(0);
});
