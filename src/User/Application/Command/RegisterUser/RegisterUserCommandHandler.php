<?php

namespace App\User\Application\Command\RegisterUser;

use App\Shared\Application\Command\CommandHandlerInterface;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface      $entityManager,
        private UserCacheManager            $userCacheManager,
    )
    {
    }

    public function __invoke(RegisterUserCommand $command): UserResponse
    {
        $user = new User();
        $user->setEmail($command->email);
        $user->setPassword($this->passwordEncoder->hashPassword($user, $command->password));
        $user->setRoles($command->roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->userCacheManager->invalidateCache($user);

        return UserResponse::fromEntity($user);
    }
}
