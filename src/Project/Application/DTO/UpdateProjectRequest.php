<?php

namespace App\Project\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProjectRequest
{
    #[Assert\Length(max: 255, maxMessage: 'Project title cannot exceed {{ limit }} characters')]
    public ?string $title = null;

    #[Assert\Length(max: 1000, maxMessage: 'Project description cannot exceed {{ limit }} characters')]
    public ?string $description = null;

    #[Assert\Type("integer", message: 'Owner ID must be an integer')]
    #[Assert\Positive(message: 'Owner ID must be a positive integer')]
    public ?int $ownerId = null;
}
