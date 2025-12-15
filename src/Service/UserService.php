<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserRepository $userRepository
    ) {
    }

    public function registerUser(string $email, string $password, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->hashPassword($user, $password));
        $user->setRoles($roles);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            throw new \InvalidArgumentException('Validation failed: ' . $errorsString);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
