<?php

declare(strict_types=1);

use App\Actions\BugReports\ApproveBugReport;
use App\Actions\BugReports\RejectBugReport;
use App\Enums\BugReportStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\BugReport;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function pendingBug(?User $reporter = null, string $code = 'ERP', array $attributes = []): BugReport
{
    $project = Project::factory()->create(['code' => $code]);
    $reporter ??= User::factory()->qa()->create();
    $project->members()->attach($reporter);

    return BugReport::factory()->create([
        'project_id' => $project->id,
        'reported_by' => $reporter->id,
        'title' => 'Tombol simpan error',
        'priority' => 'high',
        ...$attributes,
    ]);
}

it('anggota project melaporkan bug dengan bukti foto dan status menunggu', function (): void {
    Storage::fake('public');
    $project = Project::factory()->create();
    $qa = User::factory()->qa()->create();
    $project->members()->attach($qa);

    $this->actingAs($qa)->post('/bug-reports', [
        'project_id' => $project->id,
        'title' => 'Tombol simpan error',
        'steps_to_reproduce' => '1. Klik simpan',
        'priority' => 'high',
        'photos' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.jpg')],
    ])->assertRedirect('/bug-reports');

    $report = BugReport::query()->firstOrFail();
    expect($report->status)->toBe(BugReportStatus::Pending)
        ->and($report->reported_by)->toBe($qa->id)
        ->and($report->evidence_paths)->toHaveCount(2)
        ->and(Item::query()->count())->toBe(0);

    foreach ($report->evidence_paths as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

it('foto bersifat opsional', function (): void {
    $project = Project::factory()->create();
    $programmer = User::factory()->programmer()->create();
    $project->members()->attach($programmer);

    $this->actingAs($programmer)->post('/bug-reports', [
        'project_id' => $project->id,
        'title' => 'Bug tanpa foto',
        'priority' => 'low',
    ])->assertRedirect('/bug-reports');

    expect(BugReport::query()->firstOrFail()->evidence_paths)->toBeNull();
});

it('menolak file bukan gambar dan lebih dari 5 foto', function (): void {
    Storage::fake('public');
    $project = Project::factory()->create();
    $qa = User::factory()->qa()->create();
    $project->members()->attach($qa);
    $payload = ['project_id' => $project->id, 'title' => 'X', 'priority' => 'low'];

    $this->actingAs($qa)
        ->post('/bug-reports', [...$payload, 'photos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]])
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($qa)
        ->post('/bug-reports', [...$payload, 'photos' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.png"), range(1, 6))])
        ->assertSessionHasErrors('photos');

    expect(BugReport::query()->count())->toBe(0);
});

it('menolak laporan di project yang bukan keanggotaan', function (): void {
    $project = Project::factory()->create();
    $programmer = User::factory()->programmer()->create();

    $this->actingAs($programmer)->post('/bug-reports', [
        'project_id' => $project->id,
        'title' => 'X',
        'priority' => 'low',
    ])->assertForbidden();

    expect(BugReport::query()->count())->toBe(0);
});

it('chief tidak boleh melaporkan', function (): void {
    $this->actingAs(User::factory()->chief()->create())->get('/bug-reports/create')->assertForbidden();
});

it('approve otomatis membuat bug backlog atas nama pelapor dengan foto pertama', function (): void {
    $report = pendingBug(attributes: ['evidence_paths' => ['bug-reports/a.png', 'bug-reports/b.png']]);
    $manager = manager();

    $approved = app(ApproveBugReport::class)->handle($report, $manager, 'Lanjut');

    $item = Item::query()->findOrFail($approved->item_id);
    expect($approved->status)->toBe(BugReportStatus::Approved)
        ->and($approved->reviewed_by)->toBe($manager->id)
        ->and($item->type)->toBe(ItemType::Bug)
        ->and($item->status)->toBe(ItemStatus::Backlog)
        ->and($item->title)->toBe('Tombol simpan error')
        ->and($item->steps_to_reproduce)->toBe($report->steps_to_reproduce)
        ->and($item->priority->value)->toBe('high')
        ->and($item->image_path)->toBe('bug-reports/a.png')
        ->and($item->created_by)->toBe($report->reported_by)
        ->and($item->qa_id)->toBe($report->reported_by)
        ->and($item->code)->toBe('ERP-1')
        ->and($item->statusLogs()->count())->toBe(1);
});

it('approve tanpa foto membuat item tanpa gambar', function (): void {
    $approved = app(ApproveBugReport::class)->handle(pendingBug(), manager());

    expect(Item::query()->findOrFail($approved->item_id)->image_path)->toBeNull();
});

it('approve hanya boleh sekali', function (): void {
    $report = pendingBug();
    app(ApproveBugReport::class)->handle($report, manager());

    app(ApproveBugReport::class)->handle($report, manager());
})->throws(ValidationException::class);

it('hanya manager yang boleh approve', function (): void {
    app(ApproveBugReport::class)->handle(pendingBug(), User::factory()->qa()->create());
})->throws(AuthorizationException::class);

it('reject mewajibkan alasan dan tidak membuat item', function (): void {
    $report = pendingBug();

    expect(fn () => app(RejectBugReport::class)->handle($report, manager(), '  '))
        ->toThrow(ValidationException::class);

    $rejected = app(RejectBugReport::class)->handle($report, manager(), 'Bukan bug');

    expect($rejected->status)->toBe(BugReportStatus::Rejected)
        ->and($rejected->review_note)->toBe('Bukan bug')
        ->and($rejected->item_id)->toBeNull()
        ->and(Item::query()->count())->toBe(0);
});

it('temuan yang sudah ditolak tidak bisa di-approve', function (): void {
    app(ApproveBugReport::class)->handle(BugReport::factory()->rejected()->create(), manager());
})->throws(ValidationException::class);

it('manager approve lewat endpoint', function (): void {
    $report = pendingBug();

    $this->actingAs(manager())->post("/bug-reports/{$report->id}/approve")->assertRedirect();

    expect($report->fresh()->status)->toBe(BugReportStatus::Approved)
        ->and(Item::query()->count())->toBe(1);
});

it('programmer tidak bisa approve lewat endpoint', function (): void {
    $reporter = User::factory()->programmer()->create();
    $report = pendingBug($reporter);

    $this->actingAs($reporter)->post("/bug-reports/{$report->id}/approve")->assertForbidden();
});

it('non-manager hanya melihat temuan miliknya, manager melihat semua', function (): void {
    Storage::fake('public');
    $mine = pendingBug(attributes: ['evidence_paths' => ['bug-reports/a.png']]);
    pendingBug(null, 'HRD');

    $this->actingAs(User::query()->findOrFail($mine->reported_by))
        ->get('/bug-reports')
        ->assertInertia(fn (Assert $page) => $page
            ->component('bug-reports/index')
            ->has('reports.data', 1)
            ->has('reports.data.0.photos', 1)
            ->where('reports.data.0.can_review', false));

    $this->actingAs(manager())
        ->get('/bug-reports')
        ->assertInertia(fn (Assert $page) => $page
            ->has('reports.data', 2)
            ->where('counts.pending', 2)
            ->where('reports.data.0.can_review', true));
});
