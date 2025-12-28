<?php

namespace App\Comment\Application\Command\DeleteComment;

use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeleteCommentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private CommentRepositoryInterface $commentRepository,
        private CommentCacheManager        $commentCacheManager,
    )
    {
    }

    public function __invoke(DeleteCommentCommand $command): void
    {
        $comment = $this->commentRepository->find($command->id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->commentCacheManager->invalidateCache($comment);
    }
}
