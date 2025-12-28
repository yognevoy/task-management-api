<?php

namespace App\Comment\Application\Command\CreateComment;

use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CreateCommentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private TaskRepositoryInterface $taskRepository,
        private CommentCacheManager     $commentCacheManager,
    )
    {
    }

    public function __invoke(CreateCommentCommand $command): CommentResponse
    {
        $currentUser = $command->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskRepository->find($command->taskId);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comment = new Comment();
        $comment->setContent($command->content);
        $comment->setTask($task);
        $comment->setAuthor($currentUser);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->commentCacheManager->invalidateCache($comment);

        return CommentResponse::fromEntity($comment);
    }
}
