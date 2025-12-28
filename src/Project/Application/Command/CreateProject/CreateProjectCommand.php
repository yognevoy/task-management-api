<?php

namespace App\Project\Application\Command\CreateProject;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class CreateProjectCommand implements CommandInterface
{
    public function __construct(
        public readonly string  $title,
        public readonly ?string $description = null,
        public readonly ?User   $currentUser = null,
    )
    {
    }
}
