<?php

declare(strict_types=1);

use App\Actions\Items\ClaimItem;
use App\Enums\ItemStatus;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Item backlog yang siap di-claim + programmer anggota project-nya.
 *
 * @return array{Item, User}
 */
function claimable(): array
{
    $item = Item::factory()->create([
        'difficulty' => 3,
        'estimate_days' => '2.0',
        'qa_id' => User::factory()->qa(),
    ]);
    $programmer = User::factory()->programmer()->create();
    $item->project->members()->attach($programmer);

    return [$item, $programmer];
}

it('programmer meng-claim item dan item menjadi assigned', function (): void {
    [$item, $programmer] = claimable();

    $result = app(ClaimItem::class)->handle($item, $programmer);

    expect($result->status)->toBe(ItemStatus::Assigned)
        ->and($result->assignee_id)->toBe($programmer->id);

    $log = $item->statusLogs()->latest('id')->firstOrFail();
    expect($log->from_status)->toBe(ItemStatus::Backlog)
        ->and($log->to_status)->toBe(ItemStatus::Assigned)
        ->and($log->user_id)->toBe($programmer->id);
});

it('menolak claim bila self-assign dimatikan', function (): void {
    setSetting(SettingKey::SelfAssignEnabled, false);
    [$item, $programmer] = claimable();

    app(ClaimItem::class)->handle($item, $programmer);
})->throws(AuthorizationException::class);

it('menolak claim item yang belum di-triage', function (): void {
    [$item, $programmer] = claimable();
    $item->forceFill(['difficulty' => null])->save();

    app(ClaimItem::class)->handle($item, $programmer);
})->throws(AuthorizationException::class);

it('menolak claim item tanpa QA', function (): void {
    [$item, $programmer] = claimable();
    $item->forceFill(['qa_id' => null])->save();

    app(ClaimItem::class)->handle($item, $programmer);
})->throws(AuthorizationException::class);

it('menolak claim oleh programmer di luar project', function (): void {
    [$item] = claimable();

    app(ClaimItem::class)->handle($item, User::factory()->programmer()->create());
})->throws(AuthorizationException::class);

it('menolak claim oleh QA', function (): void {
    [$item] = claimable();
    $qa = User::factory()->qa()->create();
    $item->project->members()->attach($qa);

    app(ClaimItem::class)->handle($item, $qa);
})->throws(AuthorizationException::class);

it('menolak claim item yang sudah di-claim orang lain', function (): void {
    [$item, $first] = claimable();
    $second = User::factory()->programmer()->create();
    $item->project->members()->attach($second);

    app(ClaimItem::class)->handle($item, $first);
    app(ClaimItem::class)->handle($item, $second);
})->throws(ValidationException::class, 'sudah di-claim');

it('alur penuh: QA buat → manager triage → programmer claim → QA → manager approve', function (): void {
    $manager = manager();
    $qa = User::factory()->qa()->create();
    $programmer = User::factory()->programmer()->create();
    $project = Project::factory()->create();
    $project->members()->attach([$qa->id, $programmer->id]);

    $this->actingAs($qa)->post('/items', [
        'project_id' => $project->id,
        'type' => 'bug',
        'title' => 'Tombol simpan tidak bekerja',
        'priority' => 'high',
    ])->assertRedirect();

    $item = Item::query()->sole();
    expect($item->qa_id)->toBe($qa->id)
        ->and($item->status)->toBe(ItemStatus::Backlog);

    $this->actingAs($manager)
        ->put("/items/{$item->id}/triage", ['difficulty' => 3, 'estimate_days' => 1.5, 'qa_id' => $qa->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($programmer)->get('/items/available')
        ->assertInertia(fn ($page) => $page->has('items.data', 1));

    $this->actingAs($programmer)->post("/items/{$item->id}/claim")->assertRedirect();

    foreach ([
        [$programmer, 'in_progress'],
        [$programmer, 'ready_for_qa'],
        [$qa, 'qa_passed'],
        [$manager, 'done'],
    ] as [$actor, $status]) {
        $this->actingAs($actor)
            ->post("/items/{$item->id}/transitions", ['status' => $status])
            ->assertSessionHasNoErrors();
    }

    expect($item->fresh())
        ->status->toBe(ItemStatus::Done)
        ->assignee_id->toBe($programmer->id)
        ->approved_at->not->toBeNull();
});
