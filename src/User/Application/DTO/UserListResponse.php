<?php

namespace App\User\Application\DTO;

class UserListResponse
{
    public array $users;

    public function __construct(array $users)
    {
        $this->users = array_map(function ($user) {
            return UserResponse::fromEntity($user);
        }, $users);
    }
}
