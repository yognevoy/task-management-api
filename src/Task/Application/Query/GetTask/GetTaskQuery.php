<?php

namespace App\Task\Application\Query\GetTask;

use App\Shared\Application\Query\QueryInterface;
use App\User\Domain\Entity\User;

class GetTaskQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
        public readonly ?User $currentUser
    ) {}
}
