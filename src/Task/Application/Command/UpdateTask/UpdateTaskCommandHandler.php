<?php

namespace App\Task\Application\Command\UpdateTask;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\Task\Domain\Exception\CircularTaskReferenceException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class UpdateTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface     $entityManager,
        private CacheInterface             $taskCache,
    ) {}

    public function __invoke(UpdateTaskCommand $command): TaskResponse
    {
        $currentUser = $command->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskRepository->find($command->id);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        if ($command->title !== null) {
            $task->setTitle($command->title);
        }

        if ($command->description !== null) {
            $task->setDescription($command->description);
        }

        if ($command->status !== null) {
            $status = TaskStatus::from($command->status);
            $task->setStatus($status);
        }

        if ($command->type !== null) {
            $type = TaskType::from($command->type);
            $task->setType($type);
        }

        if ($command->priority !== null) {
            $priority = TaskPriority::from($command->priority);
            $task->setPriority($priority);
        }

        if ($command->dueDate !== null) {
            $task->setDueDate(new \DateTimeImmutable($command->dueDate));
        } elseif ($command->dueDate === null) {
            $task->setDueDate(null);
        }

        if ($command->parentId !== null) {
            $parentTask = $this->taskRepository->find($command->parentId);

            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }

            if ($parentTask->getId() === $task->getId()) {
                throw new CircularTaskReferenceException();
            }

            $task->setParent($parentTask);
        } elseif ($command->parentId === 0) {
            $task->setParent(null);
        }

        if ($command->projectId !== null) {
            $project = $this->projectRepository->find($command->projectId);

            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($currentUser !== null && $currentUser->getId() !== $project->getOwnerId()) {
                throw new AccessDeniedException();
            }

            $task->setProject($project);
        } elseif ($command->projectId === 0) {
            $task->setProject(null);
        }

        if ($command->assigneeId !== null) {
            $assignee = $this->userRepository->find($command->assigneeId);

            if (!$assignee) {
                throw new UserNotFoundException();
            }

            $task->setAssignee($assignee);
        } elseif ($command->assigneeId === 0) {
            $task->setAssignee(null);
        }

        $this->entityManager->flush();

        $this->invalidateCache($task);

        return TaskResponse::fromEntity($task);
    }

    private function invalidateCache(Task $task): void
    {
        $this->taskCache->delete('tasks_user_' . $task->getOwnerId());
        if ($task->getAssignee()) {
            $this->taskCache->delete('tasks_user_' . $task->getAssigneeId());
        }
        $this->taskCache->delete('tasks_all');
        $this->taskCache->delete('task_' . $task->getId());
        $this->taskCache->delete('subtasks_' . $task->getId());

        if ($task->getParentId()) {
            $this->taskCache->delete('subtasks_' . $task->getParentId());
        }
    }
}
