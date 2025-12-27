<?php

namespace App\User\Application\Service;

use App\Shared\Domain\Exception\ValidationException;
use App\User\Application\DTO\CreateUserRequest;
use App\User\Application\DTO\UpdateUserRequest;
use App\User\Application\DTO\UserListResponse;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class UserService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface      $entityManager,
        private ValidatorInterface          $validator,
        private UserRepositoryInterface     $userRepository,
        private CacheInterface              $userCache,
    )
    {
    }

    /**
     * Registers a new user.
     *
     * @param CreateUserRequest $dto
     * @return int
     * @throws ValidationException
     */
    public function registerUser(CreateUserRequest $dto): int
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordEncoder->hashPassword($user, $dto->password));
        $user->setRoles($dto->roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->invalidateCache($user);

        return $user->getId();
    }

    /**
     * Updates an existing user.
     *
     * @param int $id
     * @param UpdateUserRequest $dto
     * @return UserResponse
     * @throws ValidationException
     */
    public function updateUser(int $id, UpdateUserRequest $dto): UserResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($dto->email !== null) {
            $user->setEmail($dto->email);
        }

        if ($dto->password !== null) {
            $hashedPassword = $this->passwordEncoder->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);
        }

        if ($dto->roles !== null) {
            $user->setRoles($dto->roles);
        }

        $this->entityManager->flush();

        $this->invalidateCache($user);

        return UserResponse::fromEntity($user);
    }

    /**
     * Deletes an existing user.
     *
     * @param User $user
     * @return void
     */
    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->invalidateCache($user);
    }

    /**
     * Retrieves all users.
     *
     * @return UserListResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllUsers(): UserListResponse
    {
        $cacheKey = 'users_all';

        return $this->userCache->get($cacheKey, function () {
            $users = $this->userRepository->findAll();

            return new UserListResponse($users);
        });
    }

    /**
     * Retrieves a user by its ID.
     *
     * @param int $id
     * @return UserResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUserById(int $id): UserResponse
    {
        $cacheKey = 'user_' . $id;

        return $this->userCache->get($cacheKey, function () use ($id) {
            $user = $this->userRepository->find($id);
            if (!$user) {
                throw new UserNotFoundException();
            }

            return UserResponse::fromEntity($user);
        });
    }

    /**
     * Invalidates cache for a given user.
     *
     * @param User $user
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateCache(User $user): void
    {
        $this->userCache->delete('user_' . $user->getId());
        $this->userCache->delete('users_all');
    }
}
