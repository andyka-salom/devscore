<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BugReportStatus;
use App\Enums\Priority;
use App\Models\BugReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReport>
 */
class BugReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'reported_by' => User::factory()->qa(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'steps_to_reproduce' => "1. Buka halaman\n2. Klik simpan",
            'priority' => fake()->randomElement(Priority::cases()),
            'evidence_paths' => null,
            'status' => BugReportStatus::Pending,
        ];
    }

    /**
     * Hanya untuk menyiapkan data test; kode aplikasi memakai EscalateBugReport.
     */
    public function awaitingChief(): static
    {
        return $this->state(fn (): array => [
            'status' => BugReportStatus::AwaitingChief,
            'escalated_by' => User::factory()->manager(),
            'escalated_at' => now(),
            'escalation_note' => 'Perlu keputusan Chief.',
        ]);
    }

    /**
     * Hanya untuk menyiapkan data test; kode aplikasi memakai Approve/RejectBugReport.
     */
    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => BugReportStatus::Rejected,
            'reviewed_by' => User::factory()->manager(),
            'reviewed_at' => now(),
            'review_note' => 'Bukan bug.',
        ]);
    }
}
