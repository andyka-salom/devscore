<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BugReportStatus;
use App\Enums\Priority;
use Database\Factories\BugReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Status hanya berubah lewat ApproveBugReport / RejectBugReport (tidak fillable).
 *
 * @property BugReportStatus $status
 * @property Priority $priority
 * @property list<string>|null $evidence_paths
 * @property Carbon|null $reviewed_at
 * @property int|null $escalated_by
 * @property Carbon|null $escalated_at
 * @property string|null $escalation_note
 */
#[Fillable(['project_id', 'reported_by', 'title', 'description', 'steps_to_reproduce', 'priority', 'evidence_paths'])]
class BugReport extends Model
{
    /** @use HasFactory<BugReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BugReportStatus::class,
            'priority' => Priority::class,
            'evidence_paths' => 'array',
            'reviewed_at' => 'datetime',
            'escalated_at' => 'datetime',
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
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function escalator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function isPending(): bool
    {
        return $this->status === BugReportStatus::Pending;
    }

    public function isAwaitingChief(): bool
    {
        return $this->status === BugReportStatus::AwaitingChief;
    }
}
