<?php

namespace App\Task\Application\Controller;

use App\Task\Application\DTO\CreateTaskRequest;
use App\Task\Application\DTO\UpdateTaskRequest;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
use App\Task\Application\Service\TaskService;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService             $taskService,
        private TaskRepositoryInterface $taskRepository,
    )
    {
    }

    /**
     * Retrieves all tasks for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllTasks(Request $request): JsonResponse
    {
        return $this->json(
            $this->taskService->getAllTasks(
                $this->getUser()
            )
        );
    }

    /**
     * Retrieves a task by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        return $this->json(
            $this->taskService->getTaskById($id)
        );
    }

    /**
     * Creates a new task.
     *
     * @param CreateTaskRequest $dto
     * @return JsonResponse
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createTask(#[MapRequestPayload] CreateTaskRequest $dto): JsonResponse
    {
        return $this->json(
            $this->taskService->createTask(
                $dto, $this->getUser()
            ),
            Response::HTTP_CREATED
        );
    }

    /**
     * Updates an existing task.
     *
     * @param int $id
     * @param UpdateTaskRequest $dto
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateTask(int $id, #[MapRequestPayload] UpdateTaskRequest $dto): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);

        return $this->json(
            $this->taskService->updateTask(
                $id, $dto, $this->getUser()
            )
        );
    }

    /**
     * Deletes an existing task.
     *
     * @param int $id
     * @return JsonResponse
     */
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

    /**
     * Retrieves subtasks for a given task.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    public function getSubtasks(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        return $this->json(
            $this->taskService->getSubtasks($id)
        );
    }
}
