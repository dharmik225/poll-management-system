<?php

namespace App\Livewire\Polls;

use App\Enums\PollStatus;
use App\Models\Poll;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public bool $showForm = false;

    public bool $showDelete = false;

    public bool $showShare = false;

    public ?int $editingPollId = null;

    public ?int $deletingPollId = null;

    public string $shareUrl = '';

    public string $title = '';

    public string $description = '';

    public string $status = 'draft';

    public ?string $expiresAt = null;

    /** @var array<int, string> */
    public array $options = ['', ''];

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
    public function openEditForm(int $pollId): void
    {
        $poll = Poll::query()
            ->where('user_id', Auth::id())
            ->with('options')
            ->findOrFail($pollId);

        $this->editingPollId = $poll->id;
        $this->title = $poll->title;
        $this->description = $poll->description ?? '';
        $this->status = $poll->status->value;
        $this->expiresAt = $poll->expires_at?->format('Y-m-d\TH:i');
        $this->options = $poll->options->pluck('option')->toArray();

        if (count($this->options) < 2) {
            $this->options = array_pad($this->options, 2, '');
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
    }

    /**
     * Remove an option at the given index.
     */
    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    /**
     * Create or update a poll with its options.
     */
    public function save(): void
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

        if ($this->editingPollId) {
            $poll = Poll::query()
                ->where('user_id', Auth::id())
                ->findOrFail($this->editingPollId);

            $poll->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'expires_at' => $validated['expiresAt'],
            ]);
        } else {
            $poll = Poll::query()->create([
                'user_id' => Auth::id(),
                'slug' => Str::slug($validated['title']).'-'.Str::random(5),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'expires_at' => $validated['expiresAt'],
            ]);
        }

        $poll->options()->forceDelete();

        foreach ($validated['options'] as $index => $optionText) {
            $poll->options()->create([
                'option' => $optionText,
                'sort_order' => $index,
            ]);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Open the share modal with the poll's public URL.
     */
    public function sharePoll(int $pollId): void
    {
        $poll = Poll::query()
            ->where('user_id', Auth::id())
            ->findOrFail($pollId);

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
    public function confirmDelete(int $pollId): void
    {
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
    public function delete(): void
    {
        if ($this->deletingPollId) {
            Poll::query()
                ->where('user_id', Auth::id())
                ->where('id', $this->deletingPollId)
                ->firstOrFail()
                ->delete();
        }

        $this->showDelete = false;
        $this->deletingPollId = null;
    }

    /**
     * Refresh the polls list when any vote is broadcast via WebSocket.
     */
    #[On('echo:polls,VoteRecorded')]
    public function refreshPolls(): void
    {
        // Livewire automatically calls render() after this method.
    }

    /**
     * Render the polls index view with filtered, paginated results.
     */
    public function render(): View
    {
        $polls = Poll::query()
            ->where('user_id', Auth::id())
            ->withCount(['options', 'votes'])
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);

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
        $this->status = 'draft';
        $this->expiresAt = null;
        $this->options = ['', ''];
        $this->resetValidation();
    }
}
