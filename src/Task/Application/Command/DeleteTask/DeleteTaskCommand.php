<?php

namespace App\Task\Application\Command\DeleteTask;

use App\Shared\Application\Command\CommandInterface;

class DeleteTaskCommand implements CommandInterface
{
    public function __construct(
        public readonly int $id
    ) {}
}
