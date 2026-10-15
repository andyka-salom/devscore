<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeatureRequestKind;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priority;
use App\Models\FeatureRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureRequest>
 */
class FeatureRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'requested_by' => User::factory()->programmer(),
            'kind' => fake()->randomElement(FeatureRequestKind::cases()),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(Priority::cases()),
            'status' => FeatureRequestStatus::Pending,
        ];
    }

    /**
     * Hanya untuk menyiapkan data test; kode aplikasi memakai Approve/RejectFeatureRequest.
     */
    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => FeatureRequestStatus::Rejected,
            'reviewed_by' => User::factory()->manager(),
            'reviewed_at' => now(),
            'review_note' => 'Di luar scope.',
        ]);
    }
}
