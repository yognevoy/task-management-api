<?php

namespace App\Project\Application\Command\AddProjectMember;

use App\Config\Application\Service\ConfigurationService;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Exception\InvalidMemberException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class AddProjectMemberCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private ProjectCacheManager        $projectCacheManager,
        private ConfigurationService       $configurationService,
    )
    {
    }

    public function __invoke(AddProjectMemberCommand $command): ProjectResponse
    {
        $project = $this->projectRepository->find($command->projectId);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($project->getOwner() === $user) {
            throw InvalidMemberException::cannotAddOwnerAsMember();
        }

        $maxMembers = $this->configurationService->getMaxMembersPerProject();
        if ($project->getMembers()->count() >= $maxMembers) {
            throw InvalidMemberException::maxMembersReached($maxMembers);
        }

        $project->addMember($user);

        $this->entityManager->flush();

        $this->projectCacheManager->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }
}
