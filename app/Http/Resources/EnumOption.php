<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\HasLabel;
use BackedEnum;

/**
 * Bentuk standar enum untuk frontend: { value, label }.
 */
final class EnumOption
{
    /**
     * @return array{value: string, label: string}
     */
    public static function of(BackedEnum&HasLabel $enum): array
    {
        return ['value' => (string) $enum->value, 'label' => $enum->label()];
    }
}
