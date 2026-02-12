<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Poll $poll): bool
    {
        return $user->isAdmin() && $user->id === $poll->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->isAdmin() && $user->id === $poll->user_id;
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->isAdmin() && $user->id === $poll->user_id;
    }

    public function share(User $user, Poll $poll): bool
    {
        return $user->isAdmin() && $user->id === $poll->user_id;
    }

    public function viewVoters(User $user, Poll $poll): bool
    {
        return $user->isAdmin() && $user->id === $poll->user_id;
    }
}
