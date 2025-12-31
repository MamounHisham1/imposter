<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameHistory>
 */
class GameHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $wasImposter = fake()->boolean(30);
        $won = fake()->boolean(50);
        $eliminated = fake()->boolean(30);

        return [
            'user_id' => \App\Models\User::factory(),
            'room_id' => \App\Models\Room::factory(),
            'was_imposter' => $wasImposter,
            'won' => $won,
            'score' => fake()->numberBetween(0, 100),
            'eliminated' => $eliminated,
            'game_outcome' => $won ? ($wasImposter ? 'impostor_win' : 'crew_win') : ($wasImposter ? 'crew_win' : 'impostor_win'),
            'game_completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
