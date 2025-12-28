<?php

namespace App\User\Application\Command\UpdateUser;

use App\Shared\Application\Command\CommandInterface;

class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?array  $roles = null,
    )
    {
    }
}
