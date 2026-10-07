<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Predikat skor KPI (PRD 7.5).
 */
enum KpiGrade: string implements HasLabel
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case NeedsImprovement = 'needs_improvement';

    public static function fromScore(?float $score): ?self
    {
        return match (true) {
            $score === null => null,
            $score >= 90 => self::Excellent,
            $score >= 75 => self::Good,
            $score >= 60 => self::Fair,
            default => self::NeedsImprovement,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Sangat Baik',
            self::Good => 'Baik',
            self::Fair => 'Cukup',
            self::NeedsImprovement => 'Perlu Perbaikan',
        };
    }
}
