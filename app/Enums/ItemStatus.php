<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Satu-satunya sumber kebenaran untuk workflow status item (PRD 5.4).
 */
enum ItemStatus: string implements HasLabel
{
    case Backlog = 'backlog';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case ReadyForQa = 'ready_for_qa';
    case QaFailed = 'qa_failed';
    case QaPassed = 'qa_passed';
    case Rejected = 'rejected';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::Assigned => 'Ditugaskan',
            self::InProgress => 'Dikerjakan',
            self::OnHold => 'Ditunda',
            self::ReadyForQa => 'Siap QA',
            self::QaFailed => 'Gagal QA',
            self::QaPassed => 'Lulus QA',
            self::Rejected => 'Ditolak',
            self::Done => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Tabel transisi: status tujuan => pihak yang berwenang.
     *
     * @return array<string, list<TransitionActor>>
     */
    public function transitionMap(): array
    {
        $map = match ($this) {
            // Assignee = self-assign (claim); hanya berlaku bila pengaturan self_assign_enabled aktif.
            self::Backlog => [self::Assigned->value => [TransitionActor::Manager, TransitionActor::Assignee]],
            self::Assigned => [self::InProgress->value => [TransitionActor::Assignee]],
            self::InProgress => [
                self::OnHold->value => [TransitionActor::Assignee, TransitionActor::Manager],
                self::ReadyForQa->value => [TransitionActor::Assignee],
            ],
            self::OnHold => [self::InProgress->value => [TransitionActor::Assignee, TransitionActor::Manager]],
            self::ReadyForQa => [
                self::QaPassed->value => [TransitionActor::Qa],
                self::QaFailed->value => [TransitionActor::Qa],
            ],
            self::QaFailed, self::Rejected => [self::InProgress->value => [TransitionActor::Assignee]],
            self::QaPassed => [
                self::Done->value => [TransitionActor::Manager],
                self::Rejected->value => [TransitionActor::Manager],
            ],
            self::Done => [self::InProgress->value => [TransitionActor::Manager]],
            self::Cancelled => [],
        };

        if ($this !== self::Done && $this !== self::Cancelled) {
            $map[self::Cancelled->value] = [TransitionActor::Manager];
        }

        return $map;
    }

    /**
     * @return list<self>
     */
    public function transitions(): array
    {
        return array_map(
            static fn (string $value): self => self::from($value),
            array_keys($this->transitionMap()),
        );
    }

    public function canTransitionTo(self $to): bool
    {
        return array_key_exists($to->value, $this->transitionMap());
    }

    /**
     * @return list<TransitionActor>
     */
    public function actorsFor(self $to): array
    {
        return $this->transitionMap()[$to->value] ?? [];
    }

    public function isReopen(self $to): bool
    {
        return $this === self::Done && $to === self::InProgress;
    }

    public function requiresReason(self $to): bool
    {
        return $this->isReopen($to)
            || in_array($to, [self::QaFailed, self::Rejected, self::OnHold, self::Cancelled], true);
    }

    /**
     * Label tombol aksi untuk transisi dari status ini ke $to.
     */
    public function transitionLabel(self $to): string
    {
        return match (true) {
            $to === self::Assigned => 'Tugaskan',
            $to === self::InProgress && $this === self::Assigned => 'Mulai Kerjakan',
            $to === self::InProgress && $this === self::OnHold => 'Lanjutkan',
            $to === self::InProgress && $this === self::Done => 'Buka Kembali',
            $to === self::InProgress => 'Kerjakan Ulang',
            $to === self::OnHold => 'Tunda',
            $to === self::ReadyForQa => 'Kirim ke QA',
            $to === self::QaPassed => 'Lulus QA',
            $to === self::QaFailed => 'Gagal QA',
            $to === self::Done => 'Setujui',
            $to === self::Rejected => 'Tolak',
            $to === self::Cancelled => 'Batalkan',
            default => $to->label(),
        };
    }
}
