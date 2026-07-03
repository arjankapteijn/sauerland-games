<?php

namespace Database\Factories;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challenge>
 */
class ChallengeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 999),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(12),
            'category' => fake()->randomElement(['Eten & drinken', 'Sportief & buiten', 'Sociaal', 'Creatief', 'Nacht & verrassing']),
            'points' => fake()->randomElement([5, 10, 15, 20, 25]),
            'is_secret' => false,
            'target_team_id' => null,
            'status' => ChallengeStatus::Draft,
            'release_at' => null,
            'released_at' => null,
            'deadline_at' => null,
        ];
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Released,
            'released_at' => now(),
        ]);
    }
}
