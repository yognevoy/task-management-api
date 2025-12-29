<?php

namespace App\Project\Application\Command\RemoveProjectMember;

use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Exception\InvalidMemberException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RemoveProjectMemberCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectCacheManager        $projectCacheManager,
    )
    {
    }

    public function __invoke(RemoveProjectMemberCommand $command): ProjectResponse
    {
        $project = $this->projectRepository->find($command->projectId);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $currentUser = $command->currentUser;
        if (!$currentUser ||
            ($project->getOwner() !== $currentUser && $currentUser !== $user)) {
            throw new AccessDeniedException();
        }

        if ($project->getOwner() === $user) {
            throw InvalidMemberException::cannotRemoveOwner();
        }

        $project->removeMember($user);

        $this->entityManager->flush();

        $this->projectCacheManager->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }
}
