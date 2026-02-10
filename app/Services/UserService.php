<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function __construct(private User $userObj)
    {
        $this->userObj = $userObj;
    }

    /**
     * Create a new user.
     *
     * @param  array<string, string>  $data
     */
    public function create(array $data): User
    {
        $user = $this->userObj->create($data);

        return $user;
    }
}
