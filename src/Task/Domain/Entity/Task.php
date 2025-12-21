<?php

namespace App\Task\Domain\Entity;

use App\Comment\Domain\Entity\Comment;
use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;
use App\Enum\TaskStatus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Task
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $description = null;
    private TaskStatus $status = TaskStatus::TODO;
    private User $owner;
    private ?self $parent = null;
    private ?Project $project = null;
    private Collection $subtasks;
    private Collection $comments;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public function getOwnerId(): ?int
    {
        return $this->owner?->getId();
    }

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->subtasks = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns only the ID of the parent task, not the full object
     */
    public function getParentId(): ?int
    {
        return $this->parent?->getId();
    }

    public function getSubtasks(): Collection
    {
        return $this->subtasks;
    }

    public function addSubtask(self $subtask): static
    {
        if (!$this->subtasks->contains($subtask)) {
            $this->subtasks->add($subtask);
            $subtask->setParent($this);
        }

        return $this;
    }

    public function removeSubtask(self $subtask): static
    {
        if ($this->subtasks->removeElement($subtask)) {
            if ($subtask->getParent() === $this) {
                $subtask->setParent(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getProjectId(): ?int
    {
        return $this->project?->getId();
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTask() === $this) {
                $comment->setTask(null);
            }
        }

        return $this;
    }

    public function updateTimestamps(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
