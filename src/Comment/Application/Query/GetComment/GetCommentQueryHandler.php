<?php

namespace App\Comment\Application\Query\GetComment;

use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetCommentQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private CacheInterface             $commentCache,
    )
    {
    }

    public function __invoke(GetCommentQuery $query): CommentResponse
    {
        $cacheKey = 'comment_' . $query->id;

        return $this->commentCache->get($cacheKey, function () use ($query) {
            $comment = $this->commentRepository->find($query->id);
            if (!$comment) {
                throw new CommentNotFoundException();
            }

            return CommentResponse::fromEntity($comment);
        });
    }
}
