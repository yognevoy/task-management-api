<?php

namespace App\User\Application\Command\UpdateUser;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface      $entityManager,
        private UserRepositoryInterface     $userRepository,
        private UserCacheManager            $userCacheManager,
    )
    {
    }

    public function __invoke(UpdateUserCommand $command): UserResponse
    {
        $user = $this->userRepository->find($command->id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($command->email !== null) {
            $user->setEmail($command->email);
        }

        if ($command->password !== null) {
            $hashedPassword = $this->passwordEncoder->hashPassword($user, $command->password);
            $user->setPassword($hashedPassword);
        }

        if ($command->roles !== null) {
            $user->setRoles($command->roles);
        }

        $this->entityManager->flush();

        $this->userCacheManager->invalidateCache($user);

        return UserResponse::fromEntity($user);
    }
}
