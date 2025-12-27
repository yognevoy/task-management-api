<?php

namespace App\Task\Application\Controller;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Shared\Domain\Exception\ValidationException;
use App\Task\Application\DTO\CreateTaskRequest;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Application\DTO\UpdateTaskRequest;
use App\Task\Application\Security\Voter\TaskVoter;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $taskCache,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllTasks(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $cacheKey = 'tasks_user_' . $currentUser->getId();
        if ($currentUser->isAdmin()) {
            $cacheKey = 'tasks_all';
        }

        $taskResponses = $this->taskCache->get($cacheKey, function () use ($currentUser) {
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

            return array_map(
                fn(Task $task) => TaskResponse::fromEntity($task),
                $tasks
            );
        });

        return $this->json($taskResponses);
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getTask(int $id): JsonResponse
    {
        $cacheKey = 'task_' . $id;

        $taskResponse = $this->taskCache->get($cacheKey, function () use ($id) {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                throw new TaskNotFoundException();
            }

            $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

            return TaskResponse::fromEntity($task);
        });

        return $this->json($taskResponse);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createTask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = CreateTaskRequest::fromArray($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new ValidationException($errorMessages);
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

            $currentUser = $this->getUser();

            // Check if the user owns the project
            if (!$currentUser instanceof User || $currentUser->getId() !== $project->getOwner()->getId()) {
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

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task->setOwner($currentUser);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Invalidate cache
        $this->taskCache->delete('tasks_user_' . $currentUser->getId());
        $this->taskCache->delete('tasks_all');

        return $this->json(TaskResponse::fromEntity($task), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateTask(int $id, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);

        $data = json_decode($request->getContent(), true);

        $dto = UpdateTaskRequest::fromArray($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new ValidationException($errorMessages);
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

            $currentUser = $this->getUser();

            // Check if the user owns the project
            if (!$currentUser instanceof User || $currentUser->getId() !== $project->getOwner()->getId()) {
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

        // Invalidate cache - теперь нужно очищать DTO кеши
        $this->taskCache->delete('tasks_user_' . $task->getOwnerId());
        if ($task->getAssignee()) {
            $this->taskCache->delete('tasks_user_' . $task->getAssigneeId());
        }
        $this->taskCache->delete('tasks_all');
        $this->taskCache->delete('task_' . $task->getId());

        return $this->json(TaskResponse::fromEntity($task));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::DELETE, $task);

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        // Invalidate cache
        $this->taskCache->delete('tasks_user_' . $task->getOwnerId());
        if ($task->getAssignee()) {
            $this->taskCache->delete('tasks_user_' . $task->getAssigneeId());
        }
        $this->taskCache->delete('tasks_all');
        $this->taskCache->delete('task_' . $task->getId());

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    public function getSubtasks(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $subtasks = $this->taskRepository->findByParent($task);

        $subtaskResponses = array_map(
            fn(Task $subtask) => TaskResponse::fromEntity($subtask),
            $subtasks
        );

        return $this->json($subtaskResponses);
    }
}
