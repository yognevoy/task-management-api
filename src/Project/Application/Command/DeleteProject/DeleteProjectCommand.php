<?php

namespace App\Project\Application\Command\DeleteProject;

use App\Shared\Application\Command\CommandInterface;

class DeleteProjectCommand implements CommandInterface
{
    public function __construct(
        public readonly int $id,
    )
    {
    }
}
