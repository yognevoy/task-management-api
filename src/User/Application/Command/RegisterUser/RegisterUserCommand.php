<?php

namespace App\User\Application\Command\RegisterUser;

use App\Shared\Application\Command\CommandInterface;

class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly array  $roles = [],
    )
    {
    }
}
