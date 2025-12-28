<?php

namespace App\Comment\Application\Command\CreateComment;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class CreateCommentCommand implements CommandInterface
{
    public function __construct(
        public readonly string $content,
        public readonly int    $taskId,
        public readonly ?User  $currentUser = null,
    )
    {
    }
}
