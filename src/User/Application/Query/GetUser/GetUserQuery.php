<?php

namespace App\User\Application\Query\GetUser;

use App\Shared\Application\Query\QueryInterface;

class GetUserQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
