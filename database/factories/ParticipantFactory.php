<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'phone_number' => '+3161'.fake()->unique()->numerify('#######'),
            'team_id' => Team::factory(),
            'joined_at' => now(),
        ];
    }
}
