<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PollService
{
    public function __construct(
        private Poll $pollObj,
        private PollOption $pollOptionObj,
        private Vote $voteObj,
    ) {}

    /**
     * Create a new poll with its options for the given user.
     *
     * @param  array{title: string, description: ?string, status: string, expiresAt: ?string, options: array<int, string>, optionIds?: array<int, int|null>}  $data
     */
    public function create(int $userId, array $data): Poll
    {
        $poll = $this->pollObj->create([
            'user_id' => $userId,
            'slug' => Str::slug($data['title']).'-'.Str::random(5),
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'expires_at' => $data['expiresAt'],
        ]);

        $this->syncOptions($poll, $data['options'], $data['optionIds'] ?? []);

        return $poll;
    }

    /**
     * Update an existing poll and its options.
     *
     * @param  array{title: string, description: ?string, status: string, expiresAt: ?string, options: array<int, string>, optionIds?: array<int, int|null>}  $data
     */
    public function update(Poll $poll, array $data): Poll
    {
        $poll->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'expires_at' => $data['expiresAt'],
        ]);

        $this->syncOptions($poll, $data['options'], $data['optionIds'] ?? []);

        return $poll;
    }

    /**
     * Delete a poll.
     */
    public function delete(Poll $poll): void
    {
        $poll->delete();
    }

    /**
     * Find a poll owned by the current user with options eager-loaded.
     */
    public function findOwnedOrFail(int $pollId): Poll
    {
        return $this->pollObj
            ->ownedByCurrentUser()
            ->with('options')
            ->findOrFail($pollId);
    }

    /**
     * Find a poll owned by the current user without eager-loading.
     */
    public function findOwnedByIdOrFail(int $pollId): Poll
    {
        return $this->pollObj
            ->ownedByCurrentUser()
            ->findOrFail($pollId);
    }

    /**
     * Get paginated polls for the current user with optional search and status filter.
     */
    public function getPaginated(string $search = '', string $statusFilter = 'all', int $perPage = 10): LengthAwarePaginator
    {
        return $this->pollObj
            ->ownedByCurrentUser()
            ->withCount(['options', 'votes'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($statusFilter !== 'all', fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Sync poll options: update existing, create new, and remove only the deleted ones.
     *
     * @param  array<int, string>  $options
     * @param  array<int, int|null>  $optionIds
     */
    private function syncOptions(Poll $poll, array $options, array $optionIds = []): void
    {
        $keepIds = array_filter($optionIds, fn ($id) => $id !== null);

        $poll->options()
            ->whereNotIn('id', $keepIds)
            ->whereDoesntHave('votes')
            ->forceDelete();

        foreach ($options as $index => $optionText) {
            $existingId = $optionIds[$index] ?? null;

            if ($existingId) {
                $poll->options()->where('id', $existingId)->update([
                    'option' => $optionText,
                    'sort_order' => $index,
                ]);
            } else {
                $poll->options()->create([
                    'option' => $optionText,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
