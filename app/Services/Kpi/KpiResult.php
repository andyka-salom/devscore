<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Enums\KpiGrade;
use App\Enums\Role;
use App\Http\Resources\EnumOption;

/**
 * Hasil KPI satu user pada satu rentang. Bentuk `toArray()` juga disimpan ke kpi_snapshots.metrics.
 */
final readonly class KpiResult
{
    /**
     * @param  array<string, float|int|null>  $metrics  komponen skor & angka mentah
     * @param  list<array<string, mixed>>  $items  baris drill-down pembentuk angka
     */
    public function __construct(
        public int $userId,
        public string $name,
        public Role $role,
        public array $metrics,
        public ?float $finalScore,
        public array $items,
        public ?string $note = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $grade = KpiGrade::fromScore($this->finalScore);

        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'role' => EnumOption::of($this->role),
            'metrics' => $this->metrics,
            'final_score' => $this->finalScore,
            'grade' => $grade === null ? null : EnumOption::of($grade),
            'items' => $this->items,
            'note' => $this->note,
        ];
    }
}
