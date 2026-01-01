<?php

namespace App\Project\Application\Query\GetAllProjects;

use App\Shared\Application\Query\QueryInterface;
use App\Shared\Domain\ValueObject\Pagination;

class GetAllProjectsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $ownerId = null,
        public readonly Pagination $pagination
    )
    {
    }
}
