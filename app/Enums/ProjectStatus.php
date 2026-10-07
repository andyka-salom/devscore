<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string implements HasLabel
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Perencanaan',
            self::Active => 'Aktif',
            self::OnHold => 'Ditunda',
            self::Completed => 'Selesai',
            self::Archived => 'Diarsipkan',
        };
    }
}
