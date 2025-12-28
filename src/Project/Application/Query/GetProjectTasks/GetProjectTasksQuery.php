<?php

namespace App\Project\Application\Query\GetProjectTasks;

use App\Shared\Application\Query\QueryInterface;

class GetProjectTasksQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
