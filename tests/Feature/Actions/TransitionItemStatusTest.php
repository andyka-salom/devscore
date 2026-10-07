<?php

declare(strict_types=1);

use App\Actions\Items\TransitionItemStatus;
use App\Enums\ItemStatus;
use App\Enums\SettingKey;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function transition(Item $item, ItemStatus $to, User $actor, ?string $reason = null): Item
{
    return app(TransitionItemStatus::class)->handle($item, $to, $actor, $reason);
}

dataset('valid transitions', [
    'backlog → assigned oleh manager' => ['triaged', ItemStatus::Assigned, 'manager', null],
    'assigned → in_progress oleh assignee' => ['assigned', ItemStatus::InProgress, 'assignee', null],
    'in_progress → on_hold oleh assignee' => ['inProgress', ItemStatus::OnHold, 'assignee', 'Menunggu API vendor'],
    'in_progress → on_hold oleh manager' => ['inProgress', ItemStatus::OnHold, 'manager', 'Prioritas berubah'],
    'on_hold → in_progress oleh assignee' => ['onHold', ItemStatus::InProgress, 'assignee', null],
    'on_hold → in_progress oleh manager' => ['onHold', ItemStatus::InProgress, 'manager', null],
    'in_progress → ready_for_qa oleh assignee' => ['inProgress', ItemStatus::ReadyForQa, 'assignee', null],
    'ready_for_qa → qa_passed oleh QA' => ['readyForQa', ItemStatus::QaPassed, 'qa', null],
    'ready_for_qa → qa_failed oleh QA' => ['readyForQa', ItemStatus::QaFailed, 'qa', 'Validasi form gagal'],
    'qa_failed → in_progress oleh assignee' => ['qaFailed', ItemStatus::InProgress, 'assignee', null],
    'qa_passed → done oleh manager' => ['qaPassed', ItemStatus::Done, 'manager', null],
    'qa_passed → rejected oleh manager' => ['qaPassed', ItemStatus::Rejected, 'manager', 'Tidak sesuai requirement'],
    'rejected → in_progress oleh assignee' => ['rejected', ItemStatus::InProgress, 'assignee', null],
    'done → in_progress (reopen) oleh manager' => ['done', ItemStatus::InProgress, 'manager', 'Bug muncul lagi'],
    'assigned → cancelled oleh manager' => ['assigned', ItemStatus::Cancelled, 'manager', 'Duplikat'],
]);

function actorFor(string $actor, Item $item): User
{
    return match ($actor) {
        'manager' => manager(),
        'assignee' => assigneeOf($item),
        'qa' => qaOf($item),
    };
}

it('menjalankan transisi sah dan mencatat log', function (string $state, ItemStatus $to, string $actor, ?string $reason): void {
    $item = Item::factory()->{$state}()->create();
    $user = actorFor($actor, $item);
    $from = $item->status;

    $result = transition($item, $to, $user, $reason);

    expect($result->status)->toBe($to)
        ->and($item->fresh()->status)->toBe($to);

    $log = ItemStatusLog::query()->where('item_id', $item->id)->latest('id')->firstOrFail();
    expect($log->from_status)->toBe($from)
        ->and($log->to_status)->toBe($to)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->reason)->toBe($reason);
})->with('valid transitions');

it('menolak transisi yang tidak sah', function (): void {
    $item = Item::factory()->assigned()->create();

    transition($item, ItemStatus::Done, manager());
})->throws(ValidationException::class);

it('menolak transisi dari status cancelled', function (): void {
    $item = Item::factory()->cancelled()->create();

    transition($item, ItemStatus::InProgress, manager());
})->throws(ValidationException::class);

it('menolak programmer yang bukan assignee', function (): void {
    $item = Item::factory()->assigned()->create();

    transition($item, ItemStatus::InProgress, User::factory()->programmer()->create());
})->throws(AuthorizationException::class);

it('menolak QA yang bukan QA item', function (): void {
    $item = Item::factory()->readyForQa()->create();

    transition($item, ItemStatus::QaPassed, User::factory()->qa()->create());
})->throws(AuthorizationException::class);

it('menolak programmer yang meng-approve itemnya sendiri', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Done, assigneeOf($item));
})->throws(AuthorizationException::class);

it('menolak user nonaktif', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Done, User::factory()->manager()->inactive()->create());
})->throws(AuthorizationException::class);

it('menolak admin menjalankan approval', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Done, User::factory()->admin()->create());
})->throws(AuthorizationException::class);

it('mewajibkan alasan untuk transisi tertentu', function (string $state, ItemStatus $to, string $actor): void {
    $item = Item::factory()->{$state}()->create();

    expect(fn () => transition($item, $to, actorFor($actor, $item), '   '))
        ->toThrow(ValidationException::class);

    expect($item->fresh()->status)->not->toBe($to);
})->with([
    'qa_failed' => ['readyForQa', ItemStatus::QaFailed, 'qa'],
    'rejected' => ['qaPassed', ItemStatus::Rejected, 'manager'],
    'on_hold' => ['inProgress', ItemStatus::OnHold, 'assignee'],
    'reopen' => ['done', ItemStatus::InProgress, 'manager'],
    'cancelled' => ['inProgress', ItemStatus::Cancelled, 'manager'],
]);

it('tidak bisa keluar dari backlog sebelum di-triage', function (): void {
    $item = Item::factory()->create();

    transition($item, ItemStatus::Assigned, manager());
})->throws(ValidationException::class, 'wajib diisi sebelum item ditugaskan');

it('menambah qa_fail_count saat gagal QA', function (): void {
    $item = Item::factory()->readyForQa()->create();

    transition($item, ItemStatus::QaFailed, qaOf($item), 'Tombol simpan error');

    expect($item->fresh()->qa_fail_count)->toBe(1);
});

it('menambah reject_count saat ditolak manager', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Rejected, manager(), 'Belum sesuai');

    expect($item->fresh()->reject_count)->toBe(1);
});

it('menambah reopen_count saat item done dibuka kembali', function (): void {
    $item = Item::factory()->done()->create();

    transition($item, ItemStatus::InProgress, manager(), 'Regresi');

    expect($item->fresh())
        ->reopen_count->toBe(1)
        ->status->toBe(ItemStatus::InProgress);
});

it('menolak reopen setelah melewati batas hari dari settings', function (): void {
    setSetting(SettingKey::ReopenWindowDays, 7);
    $item = Item::factory()->done()->create(['approved_at' => now()->subDays(8)]);

    transition($item, ItemStatus::InProgress, manager(), 'Terlambat');
})->throws(ValidationException::class, 'maksimal 7 hari');

it('mengisi started_at hanya pada in_progress pertama', function (): void {
    $item = Item::factory()->assigned()->create();
    $programmer = assigneeOf($item);

    $this->travelTo(now()->setTime(9, 0));
    transition($item, ItemStatus::InProgress, $programmer);
    $firstStart = $item->fresh()->started_at;

    $this->travel(2)->hours();
    transition($item, ItemStatus::OnHold, $programmer, 'Rapat');
    transition($item, ItemStatus::InProgress, $programmer);

    expect($firstStart)->not->toBeNull()
        ->and($item->fresh()->started_at->equalTo($firstStart))->toBeTrue();
});

it('mengisi approved_at saat done', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Done, manager());

    expect($item->fresh()->approved_at)->not->toBeNull();
});

it('menulis note sistem untuk transisi dengan alasan', function (): void {
    $item = Item::factory()->readyForQa()->create();

    transition($item, ItemStatus::QaFailed, qaOf($item), 'Validasi email tidak jalan');

    $note = $item->notes()->sole();
    expect($note->is_system)->toBeTrue()
        ->and($note->body)->toBe('Gagal QA: Validasi email tidak jalan');
});

it('tidak menulis note untuk transisi tanpa alasan', function (): void {
    $item = Item::factory()->qaPassed()->create();

    transition($item, ItemStatus::Done, manager());

    expect($item->notes()->count())->toBe(0);
});

it('mengembalikan transisi yang diizinkan per user', function (): void {
    $item = Item::factory()->inProgress()->create();
    $action = app(TransitionItemStatus::class);

    $forAssignee = array_map(fn (ItemStatus $s) => $s->value, $action->allowedFor($item, assigneeOf($item)));
    $forManager = array_map(fn (ItemStatus $s) => $s->value, $action->allowedFor($item, manager()));
    $forQa = $action->allowedFor($item, qaOf($item));

    expect($forAssignee)->toEqualCanonicalizing(['on_hold', 'ready_for_qa'])
        ->and($forManager)->toEqualCanonicalizing(['on_hold', 'cancelled'])
        ->and($forQa)->toBe([]);
});

it('menyembunyikan reopen bila sudah lewat batas', function (): void {
    $item = Item::factory()->done()->create(['approved_at' => now()->subDays(31)]);

    expect(app(TransitionItemStatus::class)->allowedFor($item, manager()))->toBe([]);
});

it('menjaga log status tetap append-only', function (): void {
    $item = Item::factory()->qaPassed()->create();
    $log = $item->statusLogs()->firstOrFail();

    expect(fn () => $log->update(['reason' => 'ubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});
