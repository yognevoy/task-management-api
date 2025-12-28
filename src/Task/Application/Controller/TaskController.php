<?php

namespace App\Task\Application\Controller;

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Task\Application\Command\CreateTask\CreateTaskCommand;
use App\Task\Application\Command\DeleteTask\DeleteTaskCommand;
use App\Task\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Task\Application\DTO\CreateTaskRequest;
use App\Task\Application\DTO\UpdateTaskRequest;
use App\Task\Application\Query\GetAllTasks\GetAllTasksQuery;
use App\Task\Application\Query\GetSubtasks\GetSubtasksQuery;
use App\Task\Application\Query\GetTask\GetTaskQuery;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
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
        private CommandBusInterface     $commandBus,
        private QueryBusInterface       $queryBus,
        private TaskRepositoryInterface $taskRepository,
    )
    {
    }

    /**
     * Creates a new task.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createTask(#[MapRequestPayload] CreateTaskRequest $dto): JsonResponse
    {
        $command = new CreateTaskCommand(
            $dto->title,
            $dto->description,
            $dto->status,
            $dto->type,
            $dto->priority,
            $dto->dueDate,
            $dto->parentId,
            $dto->projectId,
            $dto->assigneeId,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing task.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateTask(int $id, #[MapRequestPayload] UpdateTaskRequest $dto): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);

        $command = new UpdateTaskCommand(
            $id,
            $dto->title,
            $dto->description,
            $dto->status,
            $dto->type,
            $dto->priority,
            $dto->dueDate,
            $dto->parentId,
            $dto->projectId,
            $dto->assigneeId,
            $dto->ownerId,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result);
    }

    /**
     * Deletes an existing task.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::DELETE, $task);

        $command = new DeleteTaskCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieves all tasks for the current user.
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllTasks(Request $request): JsonResponse
    {
        $query = new GetAllTasksQuery($this->getUser());
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves a task by its ID.
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $query = new GetTaskQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves subtasks for a given task.
     */
    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    public function getSubtasks(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $query = new GetSubtasksQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }
}
