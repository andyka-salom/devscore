<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Log status bersifat append-only: tidak boleh diubah atau dihapus.
 *
 * @property ItemStatus|null $from_status
 * @property ItemStatus $to_status
 */
#[Fillable(['item_id', 'from_status', 'to_status', 'user_id', 'reason', 'attachment_path'])]
class ItemStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('item_status_logs bersifat append-only.'));
        static::deleting(static fn (): never => throw new LogicException('item_status_logs bersifat append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ItemStatus::class,
            'to_status' => ItemStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
