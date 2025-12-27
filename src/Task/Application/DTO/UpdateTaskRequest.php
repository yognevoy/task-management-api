<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateTaskRequest
{
    #[Assert\Optional]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Optional]
    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskStatus::class, 'toValues'])]
    public ?string $status = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskType::class, 'toValues'])]
    public ?string $type = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskPriority::class, 'toValues'])]
    public ?string $priority = null;

    #[Assert\Optional]
    #[Assert\Type("string")]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
    public ?string $dueDate = null;

    #[Assert\Optional]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero]
    public ?int $parentId = null;

    #[Assert\Optional]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero]
    public ?int $projectId = null;

    #[Assert\Optional]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero]
    public ?int $assigneeId = null;
}
