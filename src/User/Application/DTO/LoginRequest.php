<?php

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email must be a valid email address')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters long')]
    public string $password;
}
