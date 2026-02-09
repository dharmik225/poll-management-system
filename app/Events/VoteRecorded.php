<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class VoteRecorded implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param  array<int, array{id: int, votes_count: int}>  $options
     */
    public function __construct(
        public int $pollId,
        public array $options,
    ) {}

    /**
     * Broadcast on the poll-specific channel and a general channel for dashboards.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('poll.'.$this->pollId),
            new Channel('polls'),
        ];
    }

    /**
     * Get the minimal data to broadcast — option IDs and their vote counts.
     *
     * @return array{poll_id: int, options: array<int, array{id: int, votes_count: int}>}
     */
    public function broadcastWith(): array
    {
        return [
            'poll_id' => $this->pollId,
            'options' => $this->options,
        ];
    }
}
