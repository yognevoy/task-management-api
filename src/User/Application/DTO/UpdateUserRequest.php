<?php

namespace App\User\Application\DTO;

use App\User\Domain\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest
{
    #[Assert\Email(message: 'Email is not valid')]
    #[Assert\Length(max: 255, maxMessage: 'Email cannot exceed {{ limit }} characters')]
    public ?string $email = null;

    #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters long')]
    #[Assert\Length(max: 255, maxMessage: 'Password cannot exceed {{ limit }} characters')]
    public ?string $password = null;

    #[Assert\All([
        new Assert\Choice(callback: [UserRole::class, 'toValues'], message: 'Invalid role value')
    ])]
    public ?array $roles = null;
}
