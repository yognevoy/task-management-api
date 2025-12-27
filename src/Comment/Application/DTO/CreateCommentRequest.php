<?php

namespace App\Comment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCommentRequest
{
    #[Assert\NotBlank(message: 'Comment content is required')]
    #[Assert\Length(max: 1000, maxMessage: 'Comment content cannot exceed {{ limit }} characters')]
    public string $content;

    #[Assert\NotBlank(message: 'Task ID is required')]
    #[Assert\Type("integer", message: 'Task ID must be an integer')]
    #[Assert\Positive(message: 'Task ID must be a positive integer')]
    public int $taskId;
}
