<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Enums\SettingKey;
use App\Services\SettingRepository;

/**
 * Pemetaan difficulty → poin dari pengaturan (PRD 7.1).
 */
final class ItemPoints
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function for(?int $difficulty): int
    {
        if ($difficulty === null) {
            return 0;
        }

        return (int) ($this->settings->array(SettingKey::DifficultyPoints)[(string) $difficulty] ?? 0);
    }
}
