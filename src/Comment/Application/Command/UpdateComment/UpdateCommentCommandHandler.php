<?php

namespace App\Comment\Application\Command\UpdateComment;

use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class UpdateCommentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private CommentRepositoryInterface $commentRepository,
        private CommentCacheManager        $commentCacheManager,
    )
    {
    }

    public function __invoke(UpdateCommentCommand $command): CommentResponse
    {
        $comment = $this->commentRepository->find($command->id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }


        if ($command->content !== null) {
            $comment->setContent($command->content);
        }

        $this->entityManager->flush();

        $this->commentCacheManager->invalidateCache($comment);

        return CommentResponse::fromEntity($comment);
    }
}
