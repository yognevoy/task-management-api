<?php

namespace App\Project\Application\Query\GetAllProjects;

use App\Shared\Application\Query\QueryInterface;

class GetAllProjectsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $ownerId = null,
    )
    {
    }
}
