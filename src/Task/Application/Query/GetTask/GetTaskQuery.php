<?php

namespace App\Task\Application\Query\GetTask;

use App\Shared\Application\Query\QueryInterface;

class GetTaskQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id
    ) {}
}
