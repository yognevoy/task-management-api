<?php

namespace App\Comment\Infrastructure\Cache;

use App\Comment\Domain\Entity\Comment;
use Symfony\Contracts\Cache\CacheInterface;

class CommentCacheManager
{
    public function __construct(
        private CacheInterface $commentCache,
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
        $this->commentCache->delete('comment_' . $comment->getId());
    }
}
