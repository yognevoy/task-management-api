<?php

namespace App\Task\Application\Query\GetSubtasks;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetSubtasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CacheInterface          $taskCache,
    )
    {
    }

    public function __invoke(GetSubtasksQuery $query): TaskListResponse
    {
        $id = $query->id;
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
}
