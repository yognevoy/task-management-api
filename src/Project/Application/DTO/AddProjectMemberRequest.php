<?php

namespace App\Project\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddProjectMemberRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'User ID is required')]
        #[Assert\Type('integer', message: 'User ID must be an integer')]
        public readonly int $userId,
    )
    {
    }
}
