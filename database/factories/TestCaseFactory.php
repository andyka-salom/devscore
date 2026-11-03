<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TestCaseResult;
use App\Models\Item;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestCase>
 */
class TestCaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'scenario' => fake()->sentence(4),
            'steps' => fake()->paragraph(),
            'expected_result' => fake()->sentence(),
            'created_by' => User::factory()->qa(),
        ];
    }

    public function passed(): static
    {
        return $this->tested(TestCaseResult::Passed);
    }

    public function failed(): static
    {
        return $this->tested(TestCaseResult::Failed);
    }

    private function tested(TestCaseResult $result): static
    {
        return $this->state(fn (): array => [
            'result' => $result,
            'actual_result' => fake()->sentence(),
            'tested_by' => User::factory()->qa(),
            'tested_at' => now(),
        ]);
    }
}
