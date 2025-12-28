<?php

namespace App\Comment\Application\Command\DeleteComment;

use App\Shared\Application\Command\CommandInterface;

class DeleteCommentCommand implements CommandInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
