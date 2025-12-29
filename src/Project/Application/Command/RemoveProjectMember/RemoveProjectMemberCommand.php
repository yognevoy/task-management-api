<?php

namespace App\Project\Application\Command\RemoveProjectMember;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class RemoveProjectMemberCommand implements CommandInterface
{
    public function __construct(
        public readonly int   $projectId,
        public readonly int   $userId,
        public readonly ?User $currentUser = null,
    )
    {
    }
}
