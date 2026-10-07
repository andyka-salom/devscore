<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemType: string implements HasLabel
{
    case Bug = 'bug';
    case Task = 'task';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Task => 'Task',
        };
    }
}
