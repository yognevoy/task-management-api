<?php

namespace App\Project\Application\Query\GetProject;

use App\Shared\Application\Query\QueryInterface;

class GetProjectQuery implements QueryInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
