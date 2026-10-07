<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\Priority;
use App\Models\Item;
use App\Models\ItemStatusLog;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * State status hanya untuk menyiapkan data test/seed; kode aplikasi tetap wajib
 * memakai TransitionItemStatus.
 *
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(ItemType::cases()),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(Priority::cases()),
            'status' => ItemStatus::Backlog,
            'created_by' => User::factory()->manager(),
        ];
    }

    public function bug(): static
    {
        return $this->state(['type' => ItemType::Bug]);
    }

    public function task(): static
    {
        return $this->state(['type' => ItemType::Task]);
    }

    /**
     * Sudah di-triage (difficulty, estimasi, assignee, QA terisi) tapi masih backlog.
     */
    public function triaged(): static
    {
        return $this->state(fn (): array => [
            'difficulty' => fake()->numberBetween(1, 5),
            'estimate_days' => fake()->randomElement(['0.5', '1.0', '1.5', '2.0', '3.0', '5.0']),
            'assignee_id' => User::factory()->programmer(),
            'qa_id' => User::factory()->qa(),
        ]);
    }

    public function assigned(): static
    {
        return $this->triaged()->inStatus(ItemStatus::Assigned);
    }

    public function inProgress(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::InProgress);
    }

    public function onHold(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::OnHold);
    }

    public function readyForQa(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::ReadyForQa);
    }

    public function qaFailed(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::QaFailed)->state(['qa_fail_count' => 1]);
    }

    public function qaPassed(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::QaPassed);
    }

    public function rejected(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::Rejected)->state(['reject_count' => 1]);
    }

    public function done(): static
    {
        return $this->triaged()->started()->inStatus(ItemStatus::Done)->state(fn (): array => [
            'approved_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->inStatus(ItemStatus::Cancelled);
    }

    private function started(): static
    {
        return $this->state(fn (): array => ['started_at' => now()->subDays(3)]);
    }

    /**
     * Set status dan tulis riwayat log yang masuk akal menuju status tersebut,
     * sehingga queued_at, durasi kerja, dan KPI QA bisa dihitung dari data factory.
     */
    private function inStatus(ItemStatus $status): static
    {
        return $this->state(['status' => $status])->afterCreating(function (Item $item) use ($status): void {
            $this->writeHistory($item, self::PATHS[$status->value]);
        });
    }

    /** Urutan status yang dilalui item untuk mencapai status akhir. */
    private const array PATHS = [
        'assigned' => ['backlog', 'assigned'],
        'in_progress' => ['backlog', 'assigned', 'in_progress'],
        'on_hold' => ['backlog', 'assigned', 'in_progress', 'on_hold'],
        'ready_for_qa' => ['backlog', 'assigned', 'in_progress', 'ready_for_qa'],
        'qa_failed' => ['backlog', 'assigned', 'in_progress', 'ready_for_qa', 'qa_failed'],
        'qa_passed' => ['backlog', 'assigned', 'in_progress', 'ready_for_qa', 'qa_passed'],
        'rejected' => ['backlog', 'assigned', 'in_progress', 'ready_for_qa', 'qa_passed', 'rejected'],
        'done' => ['backlog', 'assigned', 'in_progress', 'ready_for_qa', 'qa_passed', 'done'],
        'cancelled' => ['backlog', 'cancelled'],
    ];

    /**
     * @param  list<string>  $path
     */
    private function writeHistory(Item $item, array $path): void
    {
        $end = CarbonImmutable::instance($item->approved_at ?? now()->subHour());
        $workHours = max(1.0, (float) ($item->estimate_days ?? 1) * 24 * fake()->randomFloat(2, 0.6, 1.5));

        // Durasi (jam kalender) yang dihabiskan di setiap status sebelum pindah ke status berikutnya.
        $hoursIn = fn (string $status): float => match ($status) {
            'in_progress' => $workHours,
            'ready_for_qa' => fake()->randomFloat(1, 1, 10),
            default => fake()->randomFloat(1, 0.5, 4),
        };

        $timestamps = [];
        $cursor = $end;
        for ($i = count($path) - 1; $i >= 0; $i--) {
            $timestamps[$i] = $cursor;
            if ($i > 0) {
                $cursor = $cursor->subMinutes((int) ($hoursIn($path[$i - 1]) * 60));
            }
        }

        $previous = null;
        foreach ($path as $index => $status) {
            $log = new ItemStatusLog([
                'item_id' => $item->id,
                'from_status' => $previous,
                'to_status' => $status,
                'user_id' => $this->actorFor($item, $status),
            ]);
            $log->created_at = $timestamps[$index]->toMutable();
            $log->save();

            if ($status === 'in_progress' && $item->started_at !== null) {
                $item->forceFill(['started_at' => $timestamps[$index]])->saveQuietly();
            }

            $previous = $status;
        }
    }

    private function actorFor(Item $item, string $status): int
    {
        return match ($status) {
            'in_progress', 'ready_for_qa', 'on_hold' => $item->assignee_id ?? $item->created_by,
            'qa_passed', 'qa_failed' => $item->qa_id ?? $item->created_by,
            default => $item->created_by,
        };
    }
}
