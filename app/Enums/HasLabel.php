<?php

declare(strict_types=1);

namespace App\Enums;

interface HasLabel
{
    public function label(): string;
}
