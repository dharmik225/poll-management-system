<?php

namespace App\Livewire;

use App\Events\VoteRecorded;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
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

    public bool $showLoginModal = false;

    /**
     * Mount the component with the poll resolved by slug.
     */
    public function mount(Poll $poll): void
    {
        if (! $poll->status->canReceiveResponses()) {
            abort(404);
        }

        $this->poll = $poll;
        $this->checkExistingVote();
    }

    /**
     * Submit a vote for the selected poll option.
     */
    public function vote(): void
    {
        if (! Auth::check()) {
            $this->storeIntendedUrl();
            $this->showLoginModal = true;

            return;
        }

        $this->validate([
            'selectedOption' => ['required', 'integer', 'exists:poll_options,id'],
        ]);

        if ($this->hasVoted) {
            return;
        }

        $optionBelongsToPoll = $this->poll->options()
            ->where('id', $this->selectedOption)
            ->exists();

        if (! $optionBelongsToPoll) {
            return;
        }

        try {
            Vote::query()->create([
                'poll_id' => $this->poll->id,
                'poll_option_id' => $this->selectedOption,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
            ]);
        } catch (QueryException) {
            $this->checkExistingVote();

            return;
        }

        $this->hasVoted = true;
        $this->votedOptionId = $this->selectedOption;

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

    /**
     * Refresh vote results — triggered automatically by the Echo broadcast event.
     *
     * Livewire re-renders after this listener fires, and render() fetches
     * fresh option vote counts, so no explicit refresh is needed.
     */
    #[On('echo:poll.{poll.id},VoteRecorded')]
    public function refreshResults(): void {}

    /**
     * Render the poll vote view with computed vote data.
     */
    public function render(): View
    {
        $options = $this->poll->options()
            ->orderBy('sort_order')
            ->withCount('votes')
            ->get();

        $totalVotes = $options->sum('votes_count');

        return view('livewire.poll-vote', [
            'options' => $options,
            'totalVotes' => $totalVotes,
        ]);
    }

    /**
     * Check if the authenticated user has already voted on this poll.
     */
    private function checkExistingVote(): void
    {
        if (! Auth::check()) {
            return;
        }

        $existingVote = Vote::query()
            ->where('poll_id', $this->poll->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingVote) {
            $this->hasVoted = true;
            $this->votedOptionId = $existingVote->poll_option_id;
        }
    }

    /**
     * Store the current poll URL as the intended redirect destination after login.
     */
    private function storeIntendedUrl(): void
    {
        session()->put('url.intended', route('polls.vote', $this->poll));
    }
}
