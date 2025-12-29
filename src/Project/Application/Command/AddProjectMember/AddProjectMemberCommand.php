<?php

namespace App\Project\Application\Command\AddProjectMember;

use App\Shared\Application\Command\CommandInterface;

class AddProjectMemberCommand implements CommandInterface
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $userId,
    )
    {
    }
}
