<?php

namespace App\User\Application\DTO;

use App\User\Domain\Entity\User;

class UserResponse
{
    public int $id;
    public string $email;
    public array $roles;

    public static function fromEntity(User $user): self
    {
        $dto = new self();

        $dto->id = $user->getId();
        $dto->email = $user->getEmail();
        $dto->roles = $user->getRoles();

        return $dto;
    }
}
