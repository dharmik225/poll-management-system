<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Poll>
 */
class PollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'title' => $title,
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
