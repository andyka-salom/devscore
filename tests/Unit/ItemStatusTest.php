<?php

declare(strict_types=1);

use App\Enums\ItemStatus;
use App\Enums\TransitionActor;

it('mendefinisikan transisi sah sesuai PRD 5.4', function (ItemStatus $from, array $expected): void {
    $actual = array_map(fn (ItemStatus $s): string => $s->value, $from->transitions());

    expect($actual)->toEqualCanonicalizing($expected);
})->with([
    'backlog' => [ItemStatus::Backlog, ['assigned', 'cancelled']],
    'assigned' => [ItemStatus::Assigned, ['in_progress', 'cancelled']],
    'in_progress' => [ItemStatus::InProgress, ['on_hold', 'ready_for_qa', 'cancelled']],
    'on_hold' => [ItemStatus::OnHold, ['in_progress', 'cancelled']],
    'ready_for_qa' => [ItemStatus::ReadyForQa, ['qa_passed', 'qa_failed', 'cancelled']],
    'qa_failed' => [ItemStatus::QaFailed, ['in_progress', 'cancelled']],
    'qa_passed' => [ItemStatus::QaPassed, ['done', 'rejected', 'cancelled']],
    'rejected' => [ItemStatus::Rejected, ['in_progress', 'cancelled']],
    'done' => [ItemStatus::Done, ['in_progress']],
    'cancelled' => [ItemStatus::Cancelled, []],
]);

it('menolak transisi yang tidak ada di tabel', function (ItemStatus $from, ItemStatus $to): void {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [ItemStatus::Backlog, ItemStatus::InProgress],
    [ItemStatus::Assigned, ItemStatus::Done],
    [ItemStatus::InProgress, ItemStatus::Done],
    [ItemStatus::ReadyForQa, ItemStatus::Done],
    [ItemStatus::Done, ItemStatus::Cancelled],
    [ItemStatus::Cancelled, ItemStatus::InProgress],
]);

it('mewajibkan alasan untuk qa_failed, rejected, on_hold, reopen, dan cancelled', function (): void {
    expect(ItemStatus::ReadyForQa->requiresReason(ItemStatus::QaFailed))->toBeTrue()
        ->and(ItemStatus::QaPassed->requiresReason(ItemStatus::Rejected))->toBeTrue()
        ->and(ItemStatus::InProgress->requiresReason(ItemStatus::OnHold))->toBeTrue()
        ->and(ItemStatus::Done->requiresReason(ItemStatus::InProgress))->toBeTrue()
        ->and(ItemStatus::Assigned->requiresReason(ItemStatus::Cancelled))->toBeTrue()
        ->and(ItemStatus::Assigned->requiresReason(ItemStatus::InProgress))->toBeFalse()
        ->and(ItemStatus::QaFailed->requiresReason(ItemStatus::InProgress))->toBeFalse()
        ->and(ItemStatus::QaPassed->requiresReason(ItemStatus::Done))->toBeFalse();
});

it('memetakan pihak berwenang per transisi', function (): void {
    expect(ItemStatus::ReadyForQa->actorsFor(ItemStatus::QaPassed))->toBe([TransitionActor::Qa])
        ->and(ItemStatus::QaPassed->actorsFor(ItemStatus::Done))->toBe([TransitionActor::Manager])
        ->and(ItemStatus::InProgress->actorsFor(ItemStatus::OnHold))
        ->toBe([TransitionActor::Assignee, TransitionActor::Manager])
        ->and(ItemStatus::InProgress->actorsFor(ItemStatus::Done))->toBe([]);
});
