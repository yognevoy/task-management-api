<?php

namespace App\Task\Application\Command\UpdateTask;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\Task\Domain\Exception\CircularTaskReferenceException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class UpdateTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface     $entityManager,
        private TaskCacheManager           $taskCacheManager,
    )
    {
    }

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
        } else {
            $task->setDueDate(null);
        }

        if ($command->parentId !== null) {
            if ($command->parentId === 0) {
                $task->setParent(null);
            } else {
                $parentTask = $this->taskRepository->find($command->parentId);

                if (!$parentTask) {
                    throw new ParentTaskNotFoundException();
                }

                if ($parentTask->getId() === $task->getId()) {
                    throw new CircularTaskReferenceException();
                }

                $task->setParent($parentTask);
            }
        }

        if ($command->projectId !== null) {
            if ($command->projectId === 0) {
                $task->setProject(null);
            } else {
                $project = $this->projectRepository->find($command->projectId);

                if (!$project) {
                    throw new ProjectNotFoundException();
                }

                if ($currentUser !== null && $currentUser->getId() !== $project->getOwnerId()) {
                    throw new AccessDeniedException();
                }

                $task->setProject($project);
            }
        }

        if ($command->assigneeId !== null) {
            if ($command->assigneeId === 0) {
                $task->setAssignee(null);
            } else {
                $assignee = $this->userRepository->find($command->assigneeId);

                if (!$assignee) {
                    throw new UserNotFoundException();
                }

                $task->setAssignee($assignee);
            }
        }

        if ($command->ownerId !== null) {
            $newOwner = $this->userRepository->find($command->ownerId);
            if (!$newOwner) {
                throw new UserNotFoundException();
            }

            $task->setOwner($newOwner);
        }

        $this->entityManager->flush();

        $this->taskCacheManager->invalidateCache($task);

        return TaskResponse::fromEntity($task);
    }
}
