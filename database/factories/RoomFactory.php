<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??????')),
            'status' => fake()->randomElement(['waiting', 'playing', 'reveal_word', 'giving_hints', 'discussion', 'voting', 'results', 'finished']),
            'game_status' => fake()->randomElement(['active', 'paused', 'finished']),
            'winner' => fake()->randomElement(['imposter', 'crew', null]),
            'current_word' => fake()->word(),
            'category' => fake()->randomElement(['animals', 'food', 'places', 'objects']),
            'discussion_time' => fake()->numberBetween(30, 120),
            'phase_started_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
