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
use App\Task\Application\Service\TaskService;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
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
        private TaskService                $taskService,
        private TaskRepositoryInterface    $taskRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EntityManagerInterface     $entityManager,
        private CacheInterface             $taskCache,
        private ValidatorInterface         $validator,
    )
    {
    }

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

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        if ($dto->projectId !== null) {
            $project = $this->projectRepository->find($dto->projectId);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($currentUser->getId() !== $project->getOwner()->getId()) {
                throw new AccessDeniedException();
            }
        }

        $task = $this->taskService->createTask($dto, $currentUser);

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

        if ($dto->projectId !== null) {
            $project = $this->projectRepository->find($dto->projectId);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                throw new AccessDeniedException();
            }

            if ($currentUser->getId() !== $project->getOwner()->getId()) {
                throw new AccessDeniedException();
            }
        }

        $task = $this->taskService->updateTask($task, $dto);

        // Invalidate cache
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

        $this->taskService->deleteTask($task);

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
