<?php

namespace App\Livewire\Polls;

use App\Models\Poll;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Poll $poll;

    #[Url]
    public string $voterSearch = '';

    #[Url]
    public string $optionFilter = 'all';

    /**
     * Mount the component and verify ownership.
     */
    public function mount(Poll $poll): void
    {
        abort_unless($poll->user_id === Auth::id(), 403);

        $this->poll = $poll;
    }

    /**
     * Reset pagination when voter search changes.
     */
    public function updatedVoterSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when option filter changes.
     */
    public function updatedOptionFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Refresh poll data when a new vote is broadcast via WebSocket.
     */
    #[On('echo:poll.{poll.id},VoteRecorded')]
    public function refreshPoll(): void
    {
        $this->poll->refresh();
    }

    /**
     * Render the poll show view with statistics and voters.
     */
    public function render(): View
    {
        $poll = $this->poll->loadCount('votes');

        $options = $poll->options()
            ->withCount('votes')
            ->orderBy('sort_order')
            ->get();

        $totalVotes = $options->sum('votes_count');

        $optionStats = $options->map(function ($option) use ($totalVotes) {
            $percentage = $totalVotes > 0
                ? round(($option->votes_count / $totalVotes) * 100, 1)
                : 0;

            return (object) [
                'id' => $option->id,
                'option' => $option->option,
                'votes_count' => $option->votes_count,
                'percentage' => $percentage,
                'sort_order' => $option->sort_order,
            ];
        });

        $leadingOption = $optionStats->sortByDesc('votes_count')->first();

        $voters = $poll->votes()
            ->with(['user', 'pollOption'])
            ->when($this->voterSearch, function ($query): void {
                $query->where(function ($q): void {
                    $q->whereHas('user', function ($userQuery): void {
                        $userQuery->where('name', 'like', "%{$this->voterSearch}%")
                            ->orWhere('email', 'like', "%{$this->voterSearch}%");
                    })
                        ->orWhere('ip_address', 'like', "%{$this->voterSearch}%");
                });
            })
            ->when($this->optionFilter !== 'all', function ($query): void {
                $query->where('poll_option_id', $this->optionFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.polls.show', [
            'options' => $options,
            'optionStats' => $optionStats,
            'totalVotes' => $totalVotes,
            'leadingOption' => $leadingOption,
            'voters' => $voters,
        ]);
    }
}
