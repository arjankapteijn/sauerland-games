<?php

namespace Database\Factories;

use App\Models\Challenge;
use App\Models\ScoreEvent;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreEvent>
 */
class ScoreEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'challenge_id' => Challenge::factory(),
            'submission_id' => null,
            'points' => fake()->randomElement([5, 10, 15, 20, 25]),
            'reason' => 'opdracht voltooid',
        ];
    }
}
