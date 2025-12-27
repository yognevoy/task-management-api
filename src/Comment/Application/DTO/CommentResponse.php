<?php

namespace App\Comment\Application\DTO;

use App\Comment\Domain\Entity\Comment;

class CommentResponse
{
    public int $id;
    public string $content;
    public int $authorId;
    public int $taskId;
    public ?string $createdAt;
    public ?string $updatedAt;

    public static function fromEntity(Comment $comment): self
    {
        $dto = new self();

        $dto->id = $comment->getId();
        $dto->content = $comment->getContent();
        $dto->authorId = $comment->getAuthorId();
        $dto->taskId = $comment->getTaskId();

        $dto->createdAt = $comment->getCreatedAt() ? $comment->getCreatedAt()->format('c') : null;
        $dto->updatedAt = $comment->getUpdatedAt() ? $comment->getUpdatedAt()->format('c') : null;

        return $dto;
    }
}
