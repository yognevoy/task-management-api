<?php

namespace App\Comment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCommentRequest
{
    #[Assert\Optional]
    #[Assert\Length(max: 1000, maxMessage: 'Comment content cannot exceed {{ limit }} characters')]
    public ?string $content = null;
}
