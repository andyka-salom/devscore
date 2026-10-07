<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('manager membuat project dengan anggota dan link', function (): void {
    $programmer = User::factory()->programmer()->create();
    $qa = User::factory()->qa()->create();

    $this->actingAs(manager())->post('/projects', [
        'code' => 'crm',
        'name' => 'CRM',
        'status' => 'active',
        'member_ids' => [$programmer->id, $qa->id],
        'links' => [['label' => 'Repo', 'url' => 'https://git.example.test/crm']],
    ])->assertRedirect();

    $project = Project::query()->sole();
    expect($project->code)->toBe('CRM')
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->members()->count())->toBe(2)
        ->and($project->links()->sole()->label)->toBe('Repo');
});

it('kode project unik dan tidak berubah saat update', function (): void {
    $project = Project::factory()->create(['code' => 'ERP']);
    Project::factory()->create(['code' => 'HRIS']);
    $manager = manager();

    $this->actingAs($manager)->post('/projects', ['code' => 'ERP', 'name' => 'X', 'status' => 'active'])
        ->assertSessionHasErrors('code');

    $this->actingAs($manager)->put("/projects/{$project->id}", [
        'code' => 'NEW',
        'name' => 'ERP Baru',
        'status' => 'on_hold',
    ])->assertRedirect();

    expect($project->fresh())->code->toBe('ERP')->name->toBe('ERP Baru');
});

it('menolak URL link yang bukan http/https', function (): void {
    $this->actingAs(manager())->post('/projects', [
        'code' => 'X',
        'name' => 'X',
        'status' => 'active',
        'links' => [['label' => 'Bahaya', 'url' => 'javascript:alert(1)']],
    ])->assertSessionHasErrors('links.0.url');
});

it('non-manager tidak bisa membuat project', function (string $role): void {
    $this->actingAs(User::factory()->{$role}()->create())
        ->post('/projects', ['code' => 'X', 'name' => 'X', 'status' => 'active'])
        ->assertForbidden();
})->with(['programmer', 'qa', 'admin']);

it('programmer hanya melihat project keanggotaannya', function (): void {
    $mine = Project::factory()->create();
    $other = Project::factory()->create();
    $programmer = User::factory()->programmer()->create();
    $mine->members()->attach($programmer);

    $this->actingAs($programmer)->get('/projects')
        ->assertInertia(fn (Assert $page) => $page->component('projects/index')->has('projects', 1));

    $this->actingAs($programmer)->get("/projects/{$other->id}")->assertForbidden();
    $this->actingAs($programmer)->get("/projects/{$mine->id}")->assertOk();
});

it('menghitung progres project dari poin done, mengabaikan item cancelled', function (): void {
    $project = Project::factory()->create();
    Item::factory()->done()->create(['project_id' => $project->id, 'difficulty' => 4]); // 5 poin
    Item::factory()->inProgress()->create(['project_id' => $project->id, 'difficulty' => 3]); // 3 poin
    Item::factory()->cancelled()->create(['project_id' => $project->id, 'difficulty' => 5]);

    $this->actingAs(manager())->get("/projects/{$project->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('summary.total_points', 8)
            ->where('summary.done_points', 5)
            ->where('summary.progress', 62.5));
});
