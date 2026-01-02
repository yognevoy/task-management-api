<?php

namespace App\Task\Application\Query\GetSubtasks;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;

class GetSubtasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    )
    {
    }

    public function __invoke(GetSubtasksQuery $query): TaskListResponse
    {
        $task = $this->taskRepository->find($query->id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $subtasks = $this->taskRepository->findByParent($task);

        return new TaskListResponse($subtasks);
    }
}
