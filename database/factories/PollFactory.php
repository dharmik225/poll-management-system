<?php

namespace Database\Factories;

use App\Enums\PollStatus;
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
            'status' => fake()->randomElement(PollStatus::values()),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Set the poll status to published.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PollStatus::PUBLISHED,
        ]);
    }

    /**
     * Set the poll status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PollStatus::DRAFT,
        ]);
    }

    /**
     * Set the poll status to archived.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => PollStatus::ARCHIVED,
        ]);
    }

    /**
     * Set the poll as published but already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => PollStatus::PUBLISHED,
            'expires_at' => now()->subHour(),
        ]);
    }
}
