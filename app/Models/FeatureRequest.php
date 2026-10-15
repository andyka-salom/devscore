<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureRequestKind;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priority;
use Database\Factories\FeatureRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Status hanya berubah lewat ApproveFeatureRequest / RejectFeatureRequest (tidak fillable).
 *
 * @property FeatureRequestKind $kind
 * @property FeatureRequestStatus $status
 * @property Priority $priority
 * @property Carbon|null $reviewed_at
 */
#[Fillable(['project_id', 'requested_by', 'kind', 'title', 'description', 'priority'])]
class FeatureRequest extends Model
{
    /** @use HasFactory<FeatureRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => FeatureRequestKind::class,
            'status' => FeatureRequestStatus::class,
            'priority' => Priority::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function isPending(): bool
    {
        return $this->status === FeatureRequestStatus::Pending;
    }
}
