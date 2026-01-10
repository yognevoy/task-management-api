<?php

namespace App\Task\Application\Command\CreateTask;

use App\Config\Application\Service\ConfigurationService;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\Task\Domain\Exception\MaxAssignedTasksReachedException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CreateTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface     $entityManager,
        private TaskCacheManager           $taskCacheManager,
        private ConfigurationService       $configurationService,
    )
    {
    }

    public function __invoke(CreateTaskCommand $command): TaskResponse
    {
        $currentUser = $command->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = new Task();
        $task->setTitle($command->title);

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
        }

        if ($command->parentId !== null) {
            $parentTask = $this->taskRepository->find($command->parentId);

            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }
            $task->setParent($parentTask);
        }

        if ($command->projectId !== null) {
            $project = $this->projectRepository->find($command->projectId);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($currentUser->getId() !== $project->getOwnerId()) {
                throw new AccessDeniedException();
            }

            $task->setProject($project);
        }

        if ($command->assigneeId !== null) {
            $assignee = $this->userRepository->find($command->assigneeId);

            if (!$assignee) {
                throw new UserNotFoundException();
            }

            $maxAssignedTasks = $this->configurationService->getMaxAssignedTasksPerUser();
            $assignedTaskCount = $this->taskRepository->countByUser($assignee);

            if ($assignedTaskCount >= $maxAssignedTasks) {
                throw new MaxAssignedTasksReachedException($maxAssignedTasks);
            }

            $task->setAssignee($assignee);
        }

        $task->setOwner($currentUser);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->taskCacheManager->invalidateCache($task);

        return TaskResponse::fromEntity($task);
    }
}
