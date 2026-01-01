<?php

namespace App\User\Application\Query\GetAllUsers;

use App\Shared\Application\Query\QueryInterface;
use App\Shared\Domain\ValueObject\Pagination;

class GetAllUsersQuery implements QueryInterface
{
    public function __construct(
        public readonly Pagination $pagination
    )
    {
    }
}
