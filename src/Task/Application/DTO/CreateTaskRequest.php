<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use Symfony\Component\Validator\Constraints as Assert;

class CreateTaskRequest
{
    #[Assert\NotBlank(message: 'Task title is required')]
    #[Assert\Length(max: 255, maxMessage: 'Task title cannot exceed {{ limit }} characters')]
    public string $title;

    #[Assert\Length(max: 1000, maxMessage: 'Task description cannot exceed {{ limit }} characters')]
    public ?string $description = null;

    #[Assert\Choice(callback: [TaskStatus::class, 'toValues'], message: 'Invalid task status value')]
    public ?string $status = null;

    #[Assert\Choice(callback: [TaskType::class, 'toValues'], message: 'Invalid task type value')]
    public ?string $type = null;

    #[Assert\Choice(callback: [TaskPriority::class, 'toValues'], message: 'Invalid task priority value')]
    public ?string $priority = null;

    #[Assert\Type("string", message: 'Due date must be a valid string')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'Due date must be in valid date format')]
    public ?string $dueDate = null;

    #[Assert\Type("integer", message: 'Parent ID must be an integer')]
    #[Assert\Positive(message: 'Parent ID must be a positive integer')]
    public ?int $parentId = null;

    #[Assert\Type("integer", message: 'Project ID must be an integer')]
    #[Assert\Positive(message: 'Project ID must be a positive integer')]
    public ?int $projectId = null;

    #[Assert\Type("integer", message: 'Assignee ID must be an integer')]
    #[Assert\Positive(message: 'Assignee ID must be a positive integer')]
    public ?int $assigneeId = null;
}
