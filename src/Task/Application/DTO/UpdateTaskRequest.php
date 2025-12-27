<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateTaskRequest
{
    #[Assert\Optional]
    #[Assert\Length(max: 255, maxMessage: 'Task title cannot exceed {{ limit }} characters')]
    public ?string $title = null;

    #[Assert\Optional]
    #[Assert\Length(max: 1000, maxMessage: 'Task description cannot exceed {{ limit }} characters')]
    public ?string $description = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskStatus::class, 'toValues'], message: 'Invalid task status value')]
    public ?string $status = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskType::class, 'toValues'], message: 'Invalid task type value')]
    public ?string $type = null;

    #[Assert\Optional]
    #[Assert\Choice(callback: [TaskPriority::class, 'toValues'], message: 'Invalid task priority value')]
    public ?string $priority = null;

    #[Assert\Optional]
    #[Assert\Type("string", message: 'Due date must be a valid string')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'Due date must be in valid date format')]
    public ?string $dueDate = null;

    #[Assert\Optional]
    #[Assert\Type("integer", message: 'Parent ID must be an integer')]
    #[Assert\PositiveOrZero(message: 'Parent ID must be a positive integer or zero')]
    public ?int $parentId = null;

    #[Assert\Optional]
    #[Assert\Type("integer", message: 'Project ID must be an integer')]
    #[Assert\PositiveOrZero(message: 'Project ID must be a positive integer or zero')]
    public ?int $projectId = null;

    #[Assert\Optional]
    #[Assert\Type("integer", message: 'Assignee ID must be an integer')]
    #[Assert\PositiveOrZero(message: 'Assignee ID must be a positive integer or zero')]
    public ?int $assigneeId = null;
}
