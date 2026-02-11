<?php

namespace App\Livewire;

use App\Events\VoteRecorded;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.guest')]
class PollVote extends Component
{
    public Poll $poll;

    public ?int $selectedOption = null;

    public bool $hasVoted = false;

    public ?int $votedOptionId = null;

    public function mount(Poll $poll): void
    {
        if (! $poll->status->canReceiveResponses()) {
            abort(404);
        }

        $this->poll = $poll;
        $this->ensureVoterToken();
        $this->checkExistingVote();
    }

    /**
     * Record or update a vote for the selected option.
     */
    public function vote(): void
    {
        $this->validate([
            'selectedOption' => ['required', 'integer', 'exists:poll_options,id'],
        ]);

        if ($this->hasVoted && $this->votedOptionId === $this->selectedOption) {
            return;
        }

        if (! $this->poll->options()->where('id', $this->selectedOption)->exists()) {
            return;
        }

        $voterToken = $this->resolveVoterToken();

        $uniqueKey = Auth::check()
            ? ['poll_id' => $this->poll->id, 'user_id' => Auth::id()]
            : ['poll_id' => $this->poll->id, 'voter_token' => $voterToken];

        Vote::query()->updateOrCreate($uniqueKey, [
            'poll_option_id' => $this->selectedOption,
            'ip_address' => request()->ip(),
            'voter_token' => $voterToken,
            'user_id' => Auth::id(),
        ]);

        $this->hasVoted = true;
        $this->votedOptionId = $this->selectedOption;

        $this->broadcastVoteCounts();
    }

    /**
     * Trigger a re-render when a new vote is broadcast.
     */
    #[On('echo:poll.{poll.id},VoteRecorded')]
    public function refreshResults(): void {}

    public function render(): View
    {
        $options = $this->poll->options()
            ->orderBy('sort_order')
            ->withCount('votes')
            ->get();

        $totalVotes = $options->sum('votes_count');

        $options->each(function (PollOption $option) use ($totalVotes): void {
            $option->percentage = $this->hasVoted && $totalVotes > 0
                ? round(($option->votes_count / $totalVotes) * 100, 1)
                : 0;
            $option->isVotedOption = $this->hasVoted && $this->votedOptionId === $option->id;
        });

        return view('livewire.poll-vote', [
            'options' => $options,
            'totalVotes' => $totalVotes,
        ]);
    }

    /**
     * Set a voter_token cookie if one doesn't exist yet.
     */
    private function ensureVoterToken(): void
    {
        if (! $this->resolveVoterToken()) {
            Cookie::queue('voter_token', Str::uuid()->toString(), 60 * 24 * 365);
        }
    }

    /**
     * Resolve the voter token from the request or queued cookies.
     */
    private function resolveVoterToken(): ?string
    {
        $token = request()->cookie('voter_token');

        if ($token) {
            return $token;
        }

        foreach (Cookie::getQueuedCookies() as $queued) {
            if ($queued->getName() === 'voter_token') {
                return $queued->getValue();
            }
        }

        return null;
    }

    /**
     * Detect if the current visitor has already voted on this poll.
     */
    private function checkExistingVote(): void
    {
        if (! Auth::check() && ! $this->resolveVoterToken()) {
            return;
        }

        $existingVote = Vote::query()
            ->where('poll_id', $this->poll->id)
            ->when(
                Auth::check(),
                fn ($query) => $query->where('user_id', Auth::id()),
                fn ($query) => $query->where('voter_token', $this->resolveVoterToken()),
            )
            ->first();

        if ($existingVote) {
            $this->hasVoted = true;
            $this->votedOptionId = $existingVote->poll_option_id;
            $this->selectedOption = $existingVote->poll_option_id;
        }
    }

    /**
     * Broadcast updated vote counts to all poll viewers.
     */
    private function broadcastVoteCounts(): void
    {
        $optionVoteCounts = $this->poll->options()
            ->withCount('votes')
            ->get()
            ->map(fn (PollOption $option): array => [
                'id' => $option->id,
                'votes_count' => $option->votes_count,
            ])
            ->all();

        VoteRecorded::dispatch($this->poll->id, $optionVoteCounts);
    }
}
