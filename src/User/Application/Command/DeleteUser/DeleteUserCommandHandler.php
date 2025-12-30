<?php

namespace App\User\Application\Command\DeleteUser;

use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Exception\UserHasOwnedProjectsException;
use App\User\Domain\Exception\UserHasOwnedTasksException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;

class DeleteUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private UserRepositoryInterface    $userRepository,
        private TaskRepositoryInterface    $taskRepository,
        private ProjectRepositoryInterface $projectRepository,
        private UserCacheManager           $userCacheManager,
    )
    {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->find($command->id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $ownedTasksCount = $this->taskRepository->countByOwner($user);
        if ($ownedTasksCount > 0) {
            throw new UserHasOwnedTasksException();
        }

        $ownedProjectsCount = $this->projectRepository->countByOwner($user);
        if ($ownedProjectsCount > 0) {
            throw new UserHasOwnedProjectsException();
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->userCacheManager->invalidateCache($user);
    }
}
