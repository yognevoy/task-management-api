<?php

namespace App\Task\Application\Query\GetTask;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetTaskQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CacheInterface          $taskCache,
    )
    {
    }

    public function __invoke(GetTaskQuery $query): TaskResponse
    {
        $id = $query->id;
        $cacheKey = 'task_' . $id;

        return $this->taskCache->get($cacheKey, function () use ($id) {
            $task = $this->taskRepository->find($id);

            if (!$task) {
                throw new TaskNotFoundException();
            }

            return TaskResponse::fromEntity($task);
        });
    }
}
