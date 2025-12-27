<?php

namespace App\Project\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProjectRequest
{
    #[Assert\NotBlank(message: 'Project title is required')]
    #[Assert\Length(max: 255, maxMessage: 'Project title cannot exceed {{ limit }} characters')]
    public string $title;

    #[Assert\Optional]
    #[Assert\Length(max: 1000, maxMessage: 'Project description cannot exceed {{ limit }} characters')]
    public ?string $description = null;
}
