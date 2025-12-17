<?php

namespace App\Entity;

use App\Enum\TaskStatus;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['task:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: TaskStatus::class, options: ['default' => 'todo'])]
    #[Groups(['task:read', 'task:write'])]
    private TaskStatus $status = TaskStatus::TODO;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:write'])]
    private User $owner;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:write'])]
    private ?Project $project = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    private Collection $subtasks;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['task:read'])]
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
    #[Groups(['task:read'])]
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
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

    #[Groups(['task:read'])]
    public function getProjectId(): ?int
    {
        return $this->project?->getId();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
