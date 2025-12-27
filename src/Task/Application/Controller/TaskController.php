<?php

namespace App\Task\Application\Controller;

use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\CreateTaskRequest;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Application\DTO\UpdateTaskRequest;
use App\Task\Application\Security\Voter\TaskVoter;
use App\Task\Application\Service\TaskService;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService             $taskService,
        private TaskRepositoryInterface $taskRepository,
        private CacheInterface          $taskCache,
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
            $tasks = $this->taskService->getAllTasks($currentUser);

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
            $task = $this->taskService->getTaskById($id);

            $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

            return TaskResponse::fromEntity($task);
        });

        return $this->json($taskResponse);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createTask(#[MapRequestPayload] CreateTaskRequest $dto): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskService->createTask($dto, $currentUser);

        return $this->json(TaskResponse::fromEntity($task), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateTask(int $id, #[MapRequestPayload] UpdateTaskRequest $dto): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskService->updateTask($id, $dto, $currentUser);

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
