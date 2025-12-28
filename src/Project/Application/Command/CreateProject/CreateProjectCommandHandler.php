<?php

namespace App\Project\Application\Command\CreateProject;

use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CreateProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectCacheManager    $projectCacheManager,
    )
    {
    }

    public function __invoke(CreateProjectCommand $command): ProjectResponse
    {
        $currentUser = $command->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $project = new Project();
        $project->setTitle($command->title);

        if ($command->description !== null) {
            $project->setDescription($command->description);
        }

        $project->setOwner($currentUser);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->projectCacheManager->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }
}
