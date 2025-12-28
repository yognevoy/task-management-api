<?php

namespace App\Comment\Application\Query\GetCommentsByTask;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetCommentsByTaskQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
        private CacheInterface             $commentCache,
    )
    {
    }

    public function __invoke(GetCommentsByTaskQuery $query): CommentListResponse
    {
        $taskId = $query->taskId;
        $cacheKey = 'comments_task_' . $taskId;

        return $this->commentCache->get($cacheKey, function () use ($taskId) {
            $task = $this->taskRepository->find($taskId);
            if (!$task) {
                throw new TaskNotFoundException();
            }

            $comments = $this->commentRepository->findByTask($task);

            return new CommentListResponse($comments);
        });
    }
}
