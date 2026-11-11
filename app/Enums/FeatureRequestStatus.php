<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureRequestStatus: string implements HasLabel
{
    case Pending = 'pending';
    case AwaitingChief = 'awaiting_chief';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::AwaitingChief => 'Menunggu Chief',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
