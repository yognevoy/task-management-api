<?php

namespace App\User\Application\Service;

use App\User\Domain\Enum\UserRole;
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

class UserService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function registerUser(CreateUserRequest $dto): int
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordEncoder->hashPassword($user, $dto->password));
        $user->setRoles($dto->roles);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new ValidationException($messages);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user->getId();
    }

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

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new ValidationException($messages);
        }

        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function getAllUsers(): UserListResponse
    {
        $users = $this->userRepository->findAll();

        return new UserListResponse($users);
    }

    public function getUserById(int $id): UserResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        return UserResponse::fromEntity($user);
    }
}
