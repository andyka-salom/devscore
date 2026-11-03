<?php

declare(strict_types=1);

namespace App\Enums;

enum TestCaseResult: string implements HasLabel
{
    case Untested = 'untested';
    case Passed = 'passed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Untested => 'Belum dites',
            self::Passed => 'Lulus',
            self::Failed => 'Gagal',
        };
    }
}
