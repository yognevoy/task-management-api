<?php

namespace App\Task\Application\Command\DeleteTask;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use Doctrine\ORM\EntityManagerInterface;

class DeleteTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EntityManagerInterface $entityManager,
        private TaskCacheManager $taskCacheManager,
    ) {}

    public function __invoke(DeleteTaskCommand $command): void
    {
        $task = $this->taskRepository->find($command->id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->taskCacheManager->invalidateCache($task);
    }
}
