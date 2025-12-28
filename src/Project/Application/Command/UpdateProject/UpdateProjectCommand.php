<?php

namespace App\Project\Application\Command\UpdateProject;

use App\Shared\Application\Command\CommandInterface;

class UpdateProjectCommand implements CommandInterface
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
    )
    {
    }
}
