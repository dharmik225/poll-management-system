<?php

namespace App\Livewire\Polls;

use App\Enums\PollStatus;
use App\Models\Poll;
use App\Services\PollService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public bool $showForm = false;

    public bool $showDelete = false;

    public bool $showShare = false;

    #[Locked]
    public ?int $editingPollId = null;

    #[Locked]
    public ?int $deletingPollId = null;

    public string $shareUrl = '';

    public string $title = '';

    public string $description = '';

    public string $status = PollStatus::DRAFT->value;

    public ?string $expiresAt = null;

    /** @var array<int, string> */
    public array $options = ['', ''];

    /** @var array<int, bool> */
    #[Locked]
    public array $optionHasVotes = [];

    // for updating the option
    #[Locked]
    public array $optionIds = [];

    /**
     * Reset pagination when search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when status filter changes.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Open the create poll modal with default values.
     */
    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open the edit poll modal and populate with existing data.
     */
    public function openEditForm(PollService $pollService, int $pollId): void
    {
        $poll = $pollService->findOwnedOrFail($pollId);
        $this->authorize('update', $poll);

        $poll->load(['options' => fn ($query) => $query->withCount('votes')]);

        $this->editingPollId = $poll->id;
        $this->title = $poll->title;
        $this->description = $poll->description ?? '';
        $this->status = $poll->status->value;
        $this->expiresAt = $poll->expires_at?->format('Y-m-d\TH:i');
        $this->options = $poll->options->pluck('option')->toArray();
        $this->optionIds = $poll->options->pluck('id')->toArray();
        $this->optionHasVotes = $poll->options->map(fn ($option) => $option->votes_count > 0)->toArray();

        if (count($this->options) < 2) {
            $this->options = array_pad($this->options, 2, '');
            $this->optionIds = array_pad($this->optionIds, 2, null);
        }

        $this->showForm = true;
    }

    /**
     * Close the form modal and reset state.
     */
    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Add a new empty option to the options list.
     */
    public function addOption(): void
    {
        $this->options[] = '';
        $this->optionIds[] = null;
    }

    /**
     * Remove an option at the given index. If the option has votes, it will not be removed.
     */
    public function removeOption(int $index): void
    {
        if (count($this->options) <= 2) {
            return;
        }

        if ($this->optionHasVotes[$index] ?? false) {
            return;
        }

        unset($this->options[$index]);
        $this->options = array_values($this->options);

        unset($this->optionIds[$index]);
        $this->optionIds = array_values($this->optionIds);

        unset($this->optionHasVotes[$index]);
        $this->optionHasVotes = array_values($this->optionHasVotes);
    }

    /**
     * Create or update a poll with its options.
     */
    public function save(PollService $pollService): void
    {
        $attributes = [];
        foreach ($this->options as $index => $option) {
            $attributes["options.{$index}"] = __('Option :number', ['number' => $index + 1]);
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:'.implode(',', PollStatus::values())],
            'expiresAt' => ['nullable', 'date'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:255'],
        ], [], $attributes);

        $validated['optionIds'] = $this->optionIds;

        if ($this->editingPollId) {
            $poll = $pollService->findOwnedByIdOrFail($this->editingPollId);
            $this->authorize('update', $poll);
            $pollService->update($poll, $validated);
        } else {
            $this->authorize('create', Poll::class);
            $pollService->create(Auth::id(), $validated);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Open the share modal with the poll's public URL.
     */
    public function sharePoll(PollService $pollService, int $pollId): void
    {
        $poll = $pollService->findOwnedByIdOrFail($pollId);
        $this->authorize('share', $poll);

        $this->shareUrl = route('polls.vote', $poll->slug);
        $this->showShare = true;
    }

    /**
     * Close the share modal.
     */
    public function cancelShare(): void
    {
        $this->showShare = false;
        $this->shareUrl = '';
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(PollService $pollService, int $pollId): void
    {
        $poll = $pollService->findOwnedByIdOrFail($pollId);
        $this->authorize('delete', $poll);

        $this->deletingPollId = $pollId;
        $this->showDelete = true;
    }

    /**
     * Close the delete confirmation modal.
     */
    public function cancelDelete(): void
    {
        $this->showDelete = false;
        $this->deletingPollId = null;
    }

    /**
     * Delete the selected poll and its related data.
     */
    public function delete(PollService $pollService): void
    {
        if ($this->deletingPollId) {
            $poll = $pollService->findOwnedByIdOrFail($this->deletingPollId);
            $this->authorize('delete', $poll);
            $pollService->delete($poll);
        }

        $this->showDelete = false;
        $this->deletingPollId = null;
    }

    /**
     * Refresh the polls list when any vote is broadcast via WebSocket.
     */
    #[On('echo:polls,VoteRecorded')]
    public function refreshPolls(): void {}

    public function render(PollService $pollService): View
    {
        $polls = $pollService->getPaginated($this->search, $this->statusFilter);

        return view('livewire.polls.index', [
            'polls' => $polls,
        ]);
    }

    /**
     * Reset the form to its default state.
     */
    private function resetForm(): void
    {
        $this->editingPollId = null;
        $this->title = '';
        $this->description = '';
        $this->status = PollStatus::DRAFT->value;
        $this->expiresAt = null;
        $this->options = ['', ''];
        $this->optionIds = [];
        $this->optionHasVotes = [];
        $this->resetValidation();
    }
}
