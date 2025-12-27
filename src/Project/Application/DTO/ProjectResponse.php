<?php

namespace App\Project\Application\DTO;

use App\Project\Domain\Entity\Project;

class ProjectResponse
{
    public int $id;
    public string $title;
    public ?string $description;
    public int $ownerId;
    public ?string $createdAt;
    public ?string $updatedAt;

    public static function fromEntity(Project $project): self
    {
        $dto = new self();

        $dto->id = $project->getId();
        $dto->title = $project->getTitle();
        $dto->description = $project->getDescription();
        $dto->ownerId = $project->getOwnerId();

        $dto->createdAt = $project->getCreatedAt() ? $project->getCreatedAt()->format('c') : null;
        $dto->updatedAt = $project->getUpdatedAt() ? $project->getUpdatedAt()->format('c') : null;

        return $dto;
    }
}
