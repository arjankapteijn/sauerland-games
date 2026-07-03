<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Challenge;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'challenge_id' => Challenge::factory(),
            'team_id' => Team::factory(),
            'participant_id' => null,
            'message_author' => '+3161'.fake()->numerify('#######'),
            'message_timestamp' => fake()->unique()->numberBetween(1_700_000_000_000, 1_800_000_000_000),
            'attachment_id' => fake()->uuid(),
            'caption' => '#'.fake()->numberBetween(1, 24),
            'status' => SubmissionStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionStatus::Approved,
            'approved_by' => '+3161'.fake()->numerify('#######'),
            'approved_at' => now(),
        ]);
    }
}
