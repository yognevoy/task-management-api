<?php

namespace App\Comment\Infrastructure\Cache;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CommentCacheManager
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
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
        $commentId = $comment->getId();
        $authorId = $comment->getAuthorId();

        $tags = [
            'comments',
            'user_' . $authorId
        ];

        $results = $this->commentRepository->findRelatedUsersByComment($commentId);

        $userIds = [];
        foreach ($results as $row) {
            foreach (['author_id', 'owner_id', 'assignee_id', 'member_id'] as $key) {
                if (!empty($row[$key])) {
                    $userIds[] = $row[$key];
                }
            }
        }

        $userIds = array_unique($userIds);

        foreach ($userIds as $userId) {
            $tags[] = 'user_' . $userId;
        }

        $this->commentCache->invalidateTags($tags);

        $this->commentCache->delete('comment_' . $commentId);
    }
}
