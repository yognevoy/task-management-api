<?php

namespace App\Task\Application\Command\CreateTask;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class CreateTaskCommand implements CommandInterface
{
    public function __construct(
        public readonly string  $title,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $type,
        public readonly ?string $priority,
        public readonly ?string $dueDate,
        public readonly ?int    $parentId,
        public readonly ?int    $projectId,
        public readonly ?int    $assigneeId,
        public readonly ?User    $currentUser
    )
    {
    }
}
