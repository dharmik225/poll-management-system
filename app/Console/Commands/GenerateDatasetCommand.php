<?php

namespace App\Console\Commands;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateDatasetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll:generate-dataset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate 5 polls for admin@yopmail.com with ~10k votes each';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating dataset...');

        $admin = User::firstOrCreate(
            ['email' => 'admin@yopmail.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );

        return DB::transaction(function () use ($admin) {
            $polls = [];
            $chunkSize = 1000;
            $now = now();

            for ($i = 1; $i <= 5; $i++) {
                $title = fake()->sentence(4);
                $poll = Poll::create([
                    'user_id' => $admin->id,
                    'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
                    'title' => $title,
                    'description' => fake()->optional()->paragraph(),
                    'status' => PollStatus::PUBLISHED,
                    'expires_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
                ]);

                $optionCount = fake()->numberBetween(2, 3);
                $optionIds = [];

                for ($j = 1; $j <= $optionCount; $j++) {
                    $optionIds[] = PollOption::create([
                        'poll_id' => $poll->id,
                        'option' => fake()->sentence(3),
                        'sort_order' => $j,
                    ])->id;
                }

                $voteCount = fake()->numberBetween(9500, 10500);
                $votes = [];

                for ($j = 0; $j < $voteCount; $j++) {
                    $votes[] = [
                        'poll_id' => $poll->id,
                        'poll_option_id' => fake()->randomElement($optionIds),
                        'user_id' => null,
                        'ip_address' => fake()->ipv4(),
                        'voter_token' => fake()->uuid(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($votes) >= $chunkSize) {
                        Vote::insert($votes);
                        $votes = [];
                    }
                }

                if (! empty($votes)) {
                    Vote::insert($votes);
                }

                $polls[] = [
                    'poll' => $poll,
                    'option_count' => $optionCount,
                ];
            }

            $this->info('Dataset generated successfully!');

            return Command::SUCCESS;
        });
    }
}
