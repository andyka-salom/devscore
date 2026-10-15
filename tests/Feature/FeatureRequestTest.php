<?php

declare(strict_types=1);

use App\Actions\FeatureRequests\ApproveFeatureRequest;
use App\Actions\FeatureRequests\RejectFeatureRequest;
use App\Enums\FeatureRequestStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\FeatureRequest;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function pendingRequest(?User $requester = null, string $code = 'ERP'): FeatureRequest
{
    $project = Project::factory()->create(['code' => $code]);
    $requester ??= User::factory()->programmer()->create();
    $project->members()->attach($requester);

    return FeatureRequest::factory()->create([
        'project_id' => $project->id,
        'requested_by' => $requester->id,
        'title' => 'Export laporan ke Excel',
        'priority' => 'high',
    ]);
}

it('anggota project mengajukan permintaan fitur dengan status menunggu', function (): void {
    $project = Project::factory()->create();
    $programmer = User::factory()->programmer()->create();
    $project->members()->attach($programmer);

    $this->actingAs($programmer)->post('/feature-requests', [
        'project_id' => $project->id,
        'kind' => 'new_feature',
        'title' => 'Dashboard baru',
        'priority' => 'medium',
    ])->assertRedirect('/feature-requests');

    $request = FeatureRequest::query()->firstOrFail();
    expect($request->status)->toBe(FeatureRequestStatus::Pending)
        ->and($request->requested_by)->toBe($programmer->id)
        ->and(Item::query()->count())->toBe(0);
});

it('menolak pengajuan di project yang bukan keanggotaan', function (): void {
    $project = Project::factory()->create();
    $programmer = User::factory()->programmer()->create();

    $this->actingAs($programmer)->post('/feature-requests', [
        'project_id' => $project->id,
        'kind' => 'enhancement',
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();

    expect(FeatureRequest::query()->count())->toBe(0);
});

it('chief tidak boleh mengajukan', function (): void {
    $this->actingAs(User::factory()->chief()->create())->get('/feature-requests/create')->assertForbidden();
});

it('approve otomatis membuat task backlog atas nama pemohon', function (): void {
    $request = pendingRequest();
    $manager = manager();

    $approved = app(ApproveFeatureRequest::class)->handle($request, $manager, 'Lanjut');

    $item = Item::query()->with('project')->findOrFail($approved->item_id);
    expect($approved->status)->toBe(FeatureRequestStatus::Approved)
        ->and($approved->reviewed_by)->toBe($manager->id)
        ->and($item->type)->toBe(ItemType::Task)
        ->and($item->status)->toBe(ItemStatus::Backlog)
        ->and($item->title)->toBe('Export laporan ke Excel')
        ->and($item->priority->value)->toBe('high')
        ->and($item->created_by)->toBe($request->requested_by)
        ->and($item->code)->toBe('ERP-1')
        ->and($item->statusLogs()->count())->toBe(1);
});

it('approve hanya boleh sekali', function (): void {
    $request = pendingRequest();
    app(ApproveFeatureRequest::class)->handle($request, manager());

    app(ApproveFeatureRequest::class)->handle($request, manager());
})->throws(ValidationException::class);

it('hanya manager yang boleh approve', function (): void {
    $request = pendingRequest();

    app(ApproveFeatureRequest::class)->handle($request, User::factory()->qa()->create());
})->throws(AuthorizationException::class);

it('reject mewajibkan alasan dan tidak membuat task', function (): void {
    $request = pendingRequest();

    expect(fn () => app(RejectFeatureRequest::class)->handle($request, manager(), '  '))
        ->toThrow(ValidationException::class);

    $rejected = app(RejectFeatureRequest::class)->handle($request, manager(), 'Di luar scope');

    expect($rejected->status)->toBe(FeatureRequestStatus::Rejected)
        ->and($rejected->review_note)->toBe('Di luar scope')
        ->and($rejected->item_id)->toBeNull()
        ->and(Item::query()->count())->toBe(0);
});

it('permintaan yang sudah ditolak tidak bisa di-approve', function (): void {
    $request = FeatureRequest::factory()->rejected()->create();

    app(ApproveFeatureRequest::class)->handle($request, manager());
})->throws(ValidationException::class);

it('manager approve lewat endpoint', function (): void {
    $request = pendingRequest();

    $this->actingAs(manager())->post("/feature-requests/{$request->id}/approve")->assertRedirect();

    expect($request->fresh()->status)->toBe(FeatureRequestStatus::Approved)
        ->and(Item::query()->count())->toBe(1);
});

it('programmer tidak bisa approve lewat endpoint', function (): void {
    $requester = User::factory()->programmer()->create();
    $request = pendingRequest($requester);

    $this->actingAs($requester)->post("/feature-requests/{$request->id}/approve")->assertForbidden();
});

it('non-manager hanya melihat permintaan miliknya, manager melihat semua', function (): void {
    $mine = pendingRequest();
    pendingRequest(null, 'HRD');

    $this->actingAs(User::query()->findOrFail($mine->requested_by))
        ->get('/feature-requests')
        ->assertInertia(fn (Assert $page) => $page
            ->component('feature-requests/index')
            ->has('requests.data', 1)
            ->where('requests.data.0.can_review', false));

    $this->actingAs(manager())
        ->get('/feature-requests')
        ->assertInertia(fn (Assert $page) => $page
            ->has('requests.data', 2)
            ->where('counts.pending', 2)
            ->where('requests.data.0.can_review', true));
});
