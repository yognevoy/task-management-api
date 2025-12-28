<?php

namespace App\Task\Application\Command\UpdateTask;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class UpdateTaskCommand implements CommandInterface
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $type,
        public readonly ?string $priority,
        public readonly ?string $dueDate,
        public readonly ?int    $parentId,
        public readonly ?int    $projectId,
        public readonly ?int    $assigneeId,
        public readonly ?int    $ownerId,
        public readonly ?User   $currentUser
    )
    {
    }
}
