<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string implements HasLabel
{
    case Programmer = 'programmer';
    case Qa = 'qa';
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Programmer => 'Programmer',
            self::Qa => 'QA',
            self::Manager => 'Manager',
            self::Admin => 'Administrator',
        };
    }
}
