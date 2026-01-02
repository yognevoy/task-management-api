<?php

namespace App\Comment\Application\Query\GetCommentsByTask;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;

class GetCommentsByTaskQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
    )
    {
    }

    public function __invoke(GetCommentsByTaskQuery $query): CommentListResponse
    {
        $task = $this->taskRepository->find($query->taskId);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comments = $this->commentRepository->findByTask($task);

        return new CommentListResponse($comments);
    }
}
