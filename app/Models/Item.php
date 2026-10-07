<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\Priority;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Status & counter sengaja tidak fillable: hanya boleh diubah lewat
 * App\Actions\Items\TransitionItemStatus.
 *
 * @property ItemStatus $status
 * @property ItemType $type
 * @property Priority $priority
 * @property string|null $estimate_days
 * @property Carbon|null $due_date
 * @property Carbon|null $started_at
 * @property Carbon|null $approved_at
 */
#[Fillable([
    'project_id', 'type', 'title', 'description', 'steps_to_reproduce', 'priority',
    'difficulty', 'estimate_days', 'assignee_id', 'qa_id', 'milestone_id', 'due_date', 'created_by',
    'menu', 'category', 'is_production', 'screenshot_path',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Item $item): void {
            $item->number ??= (int) static::withTrashed()
                ->where('project_id', $item->project_id)
                ->max('number') + 1;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ItemStatus::class,
            'type' => ItemType::class,
            'priority' => Priority::class,
            'difficulty' => 'integer',
            'estimate_days' => 'decimal:1',
            'due_date' => 'date',
            'started_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_production' => 'boolean',
        ];
    }

    /**
     * Kode item, mis. ERP-42. Membutuhkan relasi project sudah di-load.
     *
     * @return Attribute<non-falsy-string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->project->code}-{$this->number}");
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function qa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ItemStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ItemStatusLog::class);
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function isTriaged(): bool
    {
        return $this->difficulty !== null && $this->estimate_days !== null;
    }
}
