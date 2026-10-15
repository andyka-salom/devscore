<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureRequestKind: string implements HasLabel
{
    case Enhancement = 'enhancement';
    case NewFeature = 'new_feature';

    public function label(): string
    {
        return match ($this) {
            self::Enhancement => 'Fitur Tambahan',
            self::NewFeature => 'Fitur Baru',
        };
    }
}
