<?php

namespace App\Comment\Application\Command\UpdateComment;

use App\Shared\Application\Command\CommandInterface;

class UpdateCommentCommand implements CommandInterface
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $content = null,
    )
    {
    }
}
