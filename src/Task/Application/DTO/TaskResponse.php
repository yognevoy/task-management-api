<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\Task;

class TaskResponse
{
    public int $id;
    public string $title;
    public ?string $description;
    public string $status;
    public string $type;
    public string $priority;
    public int $ownerId;
    public ?int $assigneeId;
    public ?int $parentId;
    public ?int $projectId;
    public ?string $dueDate;
    public ?string $createdAt;
    public ?string $updatedAt;

    public static function fromEntity(Task $task): self
    {
        $dto = new self();

        $dto->id = $task->getId();
        $dto->title = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->status = $task->getStatus()->value;
        $dto->type = $task->getType()->value;
        $dto->priority = $task->getPriority()->value;
        $dto->ownerId = $task->getOwnerId();
        $dto->assigneeId = $task->getAssigneeId();
        $dto->parentId = $task->getParentId();
        $dto->projectId = $task->getProjectId();

        $dto->dueDate = $task->getDueDate() ? $task->getDueDate()->format('c') : null;
        $dto->createdAt = $task->getCreatedAt() ? $task->getCreatedAt()->format('c') : null;
        $dto->updatedAt = $task->getUpdatedAt() ? $task->getUpdatedAt()->format('c') : null;

        return $dto;
    }
}
