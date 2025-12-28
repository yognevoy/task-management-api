<?php

namespace App\Task\Application\Query\GetAllTasks;

use App\Shared\Application\Query\QueryInterface;
use App\User\Domain\Entity\User;

class GetAllTasksQuery implements QueryInterface
{
    public function __construct(
        public readonly ?User $currentUser
    ) {}
}
