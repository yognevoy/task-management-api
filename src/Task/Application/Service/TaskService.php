<?php

namespace App\Task\Application\Service;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\CreateTaskRequest;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Application\DTO\UpdateTaskRequest;
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

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface     $entityManager,
        private CacheInterface             $taskCache,
    )
    {
    }

    public function createTask(CreateTaskRequest $dto, ?User $currentUser = null): TaskResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = new Task();
        $task->setTitle($dto->title);

        if ($dto->description !== null) {
            $task->setDescription($dto->description);
        }

        if ($dto->status !== null) {
            $status = TaskStatus::from($dto->status);
            $task->setStatus($status);
        }

        if ($dto->type !== null) {
            $type = TaskType::from($dto->type);
            $task->setType($type);
        }

        if ($dto->priority !== null) {
            $priority = TaskPriority::from($dto->priority);
            $task->setPriority($priority);
        }

        if ($dto->dueDate !== null) {
            $task->setDueDate(new \DateTimeImmutable($dto->dueDate));
        }

        if ($dto->parentId !== null) {
            $parentTask = $this->taskRepository->find($dto->parentId);

            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }
            $task->setParent($parentTask);
        }

        if ($dto->projectId !== null) {
            $project = $this->projectRepository->find($dto->projectId);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($currentUser->getId() !== $project->getOwnerId()) {
                throw new AccessDeniedException();
            }

            $task->setProject($project);
        }

        if ($dto->assigneeId !== null) {
            $assignee = $this->userRepository->find($dto->assigneeId);

            if (!$assignee) {
                throw new UserNotFoundException();
            }

            $task->setAssignee($assignee);
        }

        $task->setOwner($currentUser);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->invalidateCache($task);

        return TaskResponse::fromEntity($task);
    }

    public function updateTask(int $id, UpdateTaskRequest $dto, ?User $currentUser = null): TaskResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskRepository->find($id);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $task->setDescription($dto->description);
        }

        if ($dto->status !== null) {
            $status = TaskStatus::from($dto->status);
            $task->setStatus($status);
        }

        if ($dto->type !== null) {
            $type = TaskType::from($dto->type);
            $task->setType($type);
        }

        if ($dto->priority !== null) {
            $priority = TaskPriority::from($dto->priority);
            $task->setPriority($priority);
        }

        if ($dto->dueDate !== null) {
            $task->setDueDate(new \DateTimeImmutable($dto->dueDate));
        } elseif ($dto->dueDate === null) {
            $task->setDueDate(null);
        }

        if ($dto->parentId !== null) {
            $parentTask = $this->taskRepository->find($dto->parentId);

            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }

            if ($parentTask->getId() === $task->getId()) {
                throw new CircularTaskReferenceException();
            }

            $task->setParent($parentTask);
        } elseif ($dto->parentId === 0) {
            $task->setParent(null);
        }

        if ($dto->projectId !== null) {
            $project = $this->projectRepository->find($dto->projectId);

            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($currentUser !== null && $currentUser->getId() !== $project->getOwnerId()) {
                throw new AccessDeniedException();
            }

            $task->setProject($project);
        } elseif ($dto->projectId === 0) {
            $task->setProject(null);
        }

        if ($dto->assigneeId !== null) {
            $assignee = $this->userRepository->find($dto->assigneeId);

            if (!$assignee) {
                throw new UserNotFoundException();
            }

            $task->setAssignee($assignee);
        } elseif ($dto->assigneeId === 0) {
            $task->setAssignee(null);
        }

        $this->entityManager->flush();

        $this->invalidateCache($task);

        return TaskResponse::fromEntity($task);
    }

    public function deleteTask(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->invalidateCache($task);
    }

    public function getAllTasks(?User $currentUser = null): TaskListResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $cacheKey = 'tasks_user_' . $currentUser->getId();
        if ($currentUser->isAdmin()) {
            $cacheKey = 'tasks_all';
        }

        return $this->taskCache->get($cacheKey, function () use ($currentUser) {
            if (!$currentUser->isAdmin()) {
                $qb = $this->entityManager->createQueryBuilder();
                $tasks = $qb
                    ->select('t')
                    ->from(Task::class, 't')
                    ->leftJoin('t.project', 'p')
                    ->where('t.owner = :user OR p.owner = :user')
                    ->setParameter('user', $currentUser)
                    ->orderBy('t.id', 'ASC')
                    ->getQuery()
                    ->getResult();
            } else {
                $tasks = $this->taskRepository->findAll();
            }

            return new TaskListResponse($tasks);
        });
    }

    public function getTaskById(int $id): TaskResponse
    {
        $cacheKey = 'task_' . $id;

        return $this->taskCache->get($cacheKey, function () use ($id) {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                throw new TaskNotFoundException();
            }

            return TaskResponse::fromEntity($task);
        });
    }

    public function getSubtasks(int $id): TaskListResponse
    {
        $cacheKey = 'subtasks_' . $id;

        return $this->taskCache->get($cacheKey, function () use ($id) {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                throw new TaskNotFoundException();
            }

            $subtasks = $this->taskRepository->findByParent($task);

            return new TaskListResponse($subtasks);
        });
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
