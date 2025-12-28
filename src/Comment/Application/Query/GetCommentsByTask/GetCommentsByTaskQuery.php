<?php

namespace App\Comment\Application\Query\GetCommentsByTask;

use App\Shared\Application\Query\QueryInterface;
use App\User\Domain\Entity\User;

class GetCommentsByTaskQuery implements QueryInterface
{
    public function __construct(
        public readonly int   $taskId,
        public readonly ?User $currentUser = null,
    )
    {
    }
}
