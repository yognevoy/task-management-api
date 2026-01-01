<?php

namespace App\Comment\Infrastructure\Cache;

use App\Comment\Domain\Entity\Comment;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CommentCacheManager
{
    public function __construct(
        private TagAwareCacheInterface $commentCache,
    )
    {
    }

    /**
     * Invalidates cache for a given comment.
     *
     * @param Comment $comment
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(Comment $comment): void
    {
        $this->commentCache->invalidateTags(['comments']);
        $this->commentCache->invalidateTags(['task_' . $comment->getTaskId()]);
        $this->commentCache->invalidateTags(['author_' . $comment->getAuthorId()]);
        $this->commentCache->delete('comment_' . $comment->getId());
    }
}
