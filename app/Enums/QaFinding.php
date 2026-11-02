<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kategori bug paling berat yang ditemukan QA pada satu putaran testing.
 * Hasil testing pertama menentukan skor kualitas task (rubrik KPI Programmer).
 */
enum QaFinding: string implements HasLabel
{
    case None = 'none';
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa bug',
            self::Minor => 'Bug minor (tampilan/teks, fungsi utama berjalan)',
            self::Major => 'Bug major (fungsi/acceptance criteria tidak terpenuhi)',
            self::Critical => 'Bug critical (kehilangan data, akses tidak sah, proses utama gagal)',
        };
    }

    /**
     * Temuan yang boleh dipilih untuk keputusan QA $decision.
     *
     * @return list<self>
     */
    public static function allowedFor(ItemStatus $decision): array
    {
        return match ($decision) {
            ItemStatus::QaPassed => [self::None, self::Minor],
            ItemStatus::QaFailed => [self::Minor, self::Major, self::Critical],
            default => [],
        };
    }

    /**
     * Temuan untuk log lama yang belum mencatat kategori bug.
     */
    public static function fallbackFor(ItemStatus $decision): self
    {
        return $decision === ItemStatus::QaPassed ? self::None : self::Major;
    }
}
