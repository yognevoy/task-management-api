<?php

namespace App\Task\Application\Query\GetSubtasks;

use App\Shared\Application\Query\QueryInterface;

class GetSubtasksQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id
    ) {}
}
