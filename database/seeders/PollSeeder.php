<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Poll::factory()
            ->count(5)
            ->create()
            ->each(function (Poll $poll): void {
                $options = PollOption::factory()
                    ->count(3)
                    ->create(['poll_id' => $poll->id]);

                $options->each(function (PollOption $option) use ($poll): void {
                    Vote::factory()
                        ->count(5)
                        ->create([
                            'poll_id' => $poll->id,
                            'poll_option_id' => $option->id,
                        ]);
                });
            });
    }
}
