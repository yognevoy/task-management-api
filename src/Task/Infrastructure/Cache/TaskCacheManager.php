<?php

namespace App\Task\Infrastructure\Cache;

use App\Task\Domain\Entity\Task;
use Symfony\Contracts\Cache\CacheInterface;

class TaskCacheManager
{
    public function __construct(
        private CacheInterface $taskCache,
    )
    {
    }

    /**
     * Invalidates cache for a given task.
     *
     * @param Task $task
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(Task $task): void
    {
        $this->taskCache->delete('task_' . $task->getId());
    }
}
