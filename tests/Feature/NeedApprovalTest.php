<?php

declare(strict_types=1);

use App\Actions\BugReports\ApproveBugReport;
use App\Actions\BugReports\EscalateBugReport;
use App\Actions\BugReports\RejectBugReport;
use App\Actions\FeatureRequests\ApproveFeatureRequest;
use App\Actions\FeatureRequests\EscalateFeatureRequest;
use App\Actions\FeatureRequests\RejectFeatureRequest;
use App\Enums\BugReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\ItemType;
use App\Models\BugReport;
use App\Models\FeatureRequest;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Alur eskalasi sama untuk permintaan fitur dan temuan bug, jadi diuji lewat dataset.
 *
 * @return array<string, array{class-string, class-string, class-string, class-string, string, ItemType}>
 */
dataset('entities', [
    'permintaan fitur' => [
        FeatureRequest::class, EscalateFeatureRequest::class, ApproveFeatureRequest::class, RejectFeatureRequest::class,
        'feature-requests', ItemType::Task,
    ],
    'temuan bug' => [
        BugReport::class, EscalateBugReport::class, ApproveBugReport::class, RejectBugReport::class,
        'bug-reports', ItemType::Bug,
    ],
]);

function pendingEntity(string $model): FeatureRequest|BugReport
{
    $owner = User::factory()->programmer()->create();
    $project = Project::factory()->create();
    $project->members()->attach($owner);
    $key = $model === BugReport::class ? 'reported_by' : 'requested_by';

    return $model::factory()->create(['project_id' => $project->id, $key => $owner->id]);
}

it('manager meneruskan ke chief tanpa membuat item', function (string $model, string $escalate): void {
    $entity = pendingEntity($model);
    $manager = manager();

    $escalated = app($escalate)->handle($entity, $manager, 'Dampak besar');

    expect($escalated->status->value)->toBe('awaiting_chief')
        ->and($escalated->escalated_by)->toBe($manager->id)
        ->and($escalated->escalated_at)->not->toBeNull()
        ->and($escalated->escalation_note)->toBe('Dampak besar')
        ->and($escalated->item_id)->toBeNull()
        ->and(Item::query()->count())->toBe(0);
})->with('entities');

it('hanya manager yang boleh meneruskan', function (string $model, string $escalate): void {
    app($escalate)->handle(pendingEntity($model), chief());
})->with('entities')->throws(AuthorizationException::class);

it('hanya yang berstatus menunggu yang bisa diteruskan', function (string $model, string $escalate): void {
    $entity = pendingEntity($model);
    app($escalate)->handle($entity, manager());

    app($escalate)->handle($entity, manager());
})->with('entities')->throws(ValidationException::class);

it('chief menyetujui dan item dibuat atas nama pemohon', function (string $model, string $escalate, string $approve, string $reject, string $path, ItemType $type): void {
    $entity = pendingEntity($model);
    app($escalate)->handle($entity, manager());
    $chief = chief();

    $approved = app($approve)->handle($entity, $chief, 'Setuju');

    $item = Item::query()->findOrFail($approved->item_id);
    expect($approved->status->value)->toBe('approved')
        ->and($approved->reviewed_by)->toBe($chief->id)
        ->and($approved->escalated_by)->not->toBeNull()
        ->and($item->type)->toBe($type)
        ->and($item->created_by)->toBe($entity->{$model === BugReport::class ? 'reported_by' : 'requested_by'});
})->with('entities');

it('chief menolak dengan alasan wajib dan tidak membuat item', function (string $model, string $escalate, string $approve, string $reject): void {
    $entity = pendingEntity($model);
    app($escalate)->handle($entity, manager());
    $chief = chief();

    expect(fn () => app($reject)->handle($entity, $chief, ' '))->toThrow(ValidationException::class);

    $rejected = app($reject)->handle($entity, $chief, 'Tidak prioritas');

    expect($rejected->status->value)->toBe('rejected')
        ->and($rejected->review_note)->toBe('Tidak prioritas')
        ->and(Item::query()->count())->toBe(0);
})->with('entities');

it('manager tidak bisa memutuskan setelah diteruskan ke chief', function (string $model, string $escalate, string $approve): void {
    $entity = pendingEntity($model);
    app($escalate)->handle($entity, manager());

    app($approve)->handle($entity, manager());
})->with('entities')->throws(AuthorizationException::class);

it('chief tidak bisa memutuskan sebelum diteruskan manager', function (string $model, string $escalate, string $approve): void {
    app($approve)->handle(pendingEntity($model), chief());
})->with('entities')->throws(AuthorizationException::class);

it('manager masih bisa langsung menyetujui tanpa chief', function (string $model, string $escalate, string $approve): void {
    $approved = app($approve)->handle(pendingEntity($model), manager());

    expect($approved->status->value)->toBe('approved')->and(Item::query()->count())->toBe(1);
})->with('entities');

it('endpoint: manager meneruskan, chief menyetujui', function (string $model, string $escalate, string $approve, string $reject, string $path): void {
    $entity = pendingEntity($model);

    $this->actingAs(manager())->post("/{$path}/{$entity->id}/escalate", ['note' => 'Cek'])->assertRedirect();
    expect($entity->fresh()->status->value)->toBe('awaiting_chief');

    $this->actingAs(chief())->post("/{$path}/{$entity->id}/approve")->assertRedirect();
    expect($entity->fresh()->status->value)->toBe('approved')->and(Item::query()->count())->toBe(1);
})->with('entities');

it('endpoint: programmer tidak bisa meneruskan', function (string $model, string $escalate, string $approve, string $reject, string $path): void {
    $entity = pendingEntity($model);

    $this->actingAs(User::query()->findOrFail($entity->{$model === BugReport::class ? 'reported_by' : 'requested_by'}))
        ->post("/{$path}/{$entity->id}/escalate")
        ->assertForbidden();
})->with('entities');

it('menu need approval hanya untuk chief dan menampilkan antrian gabungan', function (): void {
    $feature = pendingEntity(FeatureRequest::class);
    $bug = pendingEntity(BugReport::class);
    pendingEntity(FeatureRequest::class); // tidak diteruskan, tidak boleh muncul
    app(EscalateFeatureRequest::class)->handle($feature, manager());
    app(EscalateBugReport::class)->handle($bug, manager());

    $this->actingAs(chief())->get('/need-approval')->assertInertia(fn (Assert $page) => $page
        ->component('need-approval/index')
        ->has('features', 1)
        ->has('bugs', 1)
        ->where('features.0.can_review', true)
        ->where('bugs.0.can_review', true));

    $this->actingAs(chief())->get('/')->assertInertia(fn (Assert $page) => $page->where('nav.needApproval', 2));

    $this->actingAs(manager())->get('/need-approval')->assertForbidden();
    $this->actingAs(User::factory()->programmer()->create())->get('/need-approval')->assertForbidden();
    $this->actingAs(manager())->get('/')->assertInertia(fn (Assert $page) => $page->where('nav.needApproval', null));
});

it('tombol teruskan hanya tersedia bagi manager pada status menunggu', function (): void {
    pendingEntity(FeatureRequest::class);

    $this->actingAs(manager())->get('/feature-requests')->assertInertia(fn (Assert $page) => $page
        ->where('requests.data.0.can_escalate', true)
        ->where('counts.awaiting_chief', 0));

    $this->actingAs(chief())->get('/feature-requests')->assertInertia(fn (Assert $page) => $page
        ->where('requests.data.0.can_escalate', false)
        ->where('requests.data.0.can_review', false));
});

it('status awaiting_chief tercatat di daftar dan bisa difilter', function (): void {
    FeatureRequest::factory()->awaitingChief()->create();
    BugReport::factory()->awaitingChief()->create();

    $this->actingAs(manager())->get('/feature-requests?status=awaiting_chief')->assertInertia(fn (Assert $page) => $page
        ->has('requests.data', 1)
        ->where('counts.awaiting_chief', 1)
        ->where('requests.data.0.can_review', false));

    $this->actingAs(manager())->get('/bug-reports?status=awaiting_chief')->assertInertia(fn (Assert $page) => $page
        ->has('reports.data', 1)
        ->where('counts.awaiting_chief', 1));

    expect(FeatureRequestStatus::AwaitingChief->label())->toBe('Menunggu Chief')
        ->and(BugReportStatus::AwaitingChief->label())->toBe('Menunggu Chief');
});
