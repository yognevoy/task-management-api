<?php

namespace App\Comment\Application\Query\GetComment;

use App\Shared\Application\Query\QueryInterface;

class GetCommentQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
