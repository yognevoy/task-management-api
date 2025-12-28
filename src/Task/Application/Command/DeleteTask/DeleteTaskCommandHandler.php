<?php

namespace App\Task\Application\Command\DeleteTask;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DeleteTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $taskCache,
    ) {}

    public function __invoke(DeleteTaskCommand $command): void
    {
        $task = $this->taskRepository->find($command->id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->invalidateCache($task);
    }

    private function invalidateCache(Task $task): void
    {
        $this->taskCache->delete('tasks_user_' . $task->getOwnerId());
        if ($task->getAssignee()) {
            $this->taskCache->delete('tasks_user_' . $task->getAssigneeId());
        }
        $this->taskCache->delete('tasks_all');
        $this->taskCache->delete('task_' . $task->getId());
        $this->taskCache->delete('subtasks_' . $task->getId());

        if ($task->getParentId()) {
            $this->taskCache->delete('subtasks_' . $task->getParentId());
        }
    }
}
