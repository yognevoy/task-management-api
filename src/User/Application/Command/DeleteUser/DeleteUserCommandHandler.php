<?php

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;

class DeleteUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private UserRepositoryInterface $userRepository,
        private UserCacheManager        $userCacheManager,
    )
    {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->find($command->id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->userCacheManager->invalidateCache($user);
    }
}
