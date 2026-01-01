<?php

namespace App\Comment\Application\Query\GetAllComments;

use App\Shared\Application\Query\QueryInterface;
use App\Shared\Domain\ValueObject\Pagination;
use App\User\Domain\Entity\User;

class GetAllCommentsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int  $taskId = null,
        public readonly ?int  $authorId = null,
        public readonly ?User $currentUser = null,
        public readonly Pagination $pagination
    )
    {
    }
}
