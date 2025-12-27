<?php

namespace App\Comment\Application\DTO;

class CommentListResponse
{
    public array $comments;

    public function __construct(array $comments)
    {
        $this->comments = array_map(function ($comment) {
            return CommentResponse::fromEntity($comment);
        }, $comments);
    }
}
