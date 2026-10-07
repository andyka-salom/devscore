<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Pihak yang berwenang menjalankan sebuah transisi status item.
 */
enum TransitionActor: string
{
    case Assignee = 'assignee';
    case Qa = 'qa';
    case Manager = 'manager';
}
