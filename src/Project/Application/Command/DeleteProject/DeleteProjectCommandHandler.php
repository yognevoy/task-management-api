<?php

namespace App\Project\Application\Command\DeleteProject;

use App\Project\Domain\Exception\ProjectHasTasksException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeleteProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private ProjectRepositoryInterface $projectRepository,
        private ProjectCacheManager        $projectCacheManager,
    )
    {
    }

    public function __invoke(DeleteProjectCommand $command): void
    {
        $project = $this->projectRepository->find($command->id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $taskCount = $this->projectRepository->countTasks($project);
        if ($taskCount > 0) {
            throw new ProjectHasTasksException();
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        $this->projectCacheManager->invalidateCache($project);
    }
}
