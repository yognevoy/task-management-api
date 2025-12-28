<?php

namespace App\Project\Application\Command\UpdateProject;

use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class UpdateProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectCacheManager        $projectCacheManager,
    )
    {
    }

    public function __invoke(UpdateProjectCommand $command): ProjectResponse
    {
        $project = $this->projectRepository->find($command->id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        if ($command->title !== null) {
            $project->setTitle($command->title);
        }

        if ($command->description !== null) {
            $project->setDescription($command->description);
        }

        if ($command->ownerId !== null) {
            $newOwner = $this->userRepository->find($command->ownerId);
            if (!$newOwner) {
                throw new UserNotFoundException();
            }

            $project->setOwner($newOwner);
        }

        $this->entityManager->flush();

        $this->projectCacheManager->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }
}
