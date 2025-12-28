<?php

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Application\Command\CommandInterface;

class DeleteUserCommand implements CommandInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
