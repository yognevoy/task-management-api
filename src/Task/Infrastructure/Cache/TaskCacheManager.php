<?php

namespace App\Task\Infrastructure\Cache;

use App\Task\Domain\Entity\Task;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TaskCacheManager
{
    public function __construct(
        private TagAwareCacheInterface $taskCache,
    ) {}

    /**
     * Invalidates cache for a given task.
     *
     * @param Task $task
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(Task $task): void
    {
        $tags = ['user_' . $task->getOwnerId()];

        if ($task->getAssignee()) {
            $tags[] = 'user_' . $task->getAssigneeId();
        }

        if ($task->getProject()) {
            foreach ($task->getProject()->getMembers() as $member) {
                $tags[] = 'user_' . $member->getId();
            }
        }

        $this->taskCache->invalidateTags($tags);

        $this->taskCache->delete('task_' . $task->getId());
        $this->taskCache->delete('subtasks_' . $task->getId());

        if ($task->getParentId()) {
            $this->taskCache->delete('subtasks_' . $task->getParentId());
        }
    }
}
