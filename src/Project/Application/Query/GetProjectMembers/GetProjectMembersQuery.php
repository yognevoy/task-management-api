<?php

namespace App\Project\Application\Query\GetProjectMembers;

use App\Shared\Application\Query\QueryInterface;

class GetProjectMembersQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
