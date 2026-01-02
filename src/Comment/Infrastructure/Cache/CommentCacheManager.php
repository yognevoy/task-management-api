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
        $tags = [
            'comments',
            'task_' . $comment->getTaskId(),
            'author_' . $comment->getAuthorId(),
            'user_' . $comment->getAuthorId()
        ];

        $task = $comment->getTask();

        if ($task) {
            if ($task->getOwnerId()) {
                $tags[] = 'user_' . $task->getOwnerId();
            }

            if ($task->getAssignee()) {
                $tags[] = 'user_' . $task->getAssigneeId();
            }

            $project = $task->getProject();

            if ($project) {
                if ($project->getOwnerId()) {
                    $tags[] = 'user_' . $project->getOwnerId();
                }

                foreach ($task->getProject()->getMembers() as $member) {
                    $tags[] = 'user_' . $member->getId();
                }
            }
        }

        $this->commentCache->invalidateTags($tags);

        $this->commentCache->delete('comment_' . $comment->getId());
    }
}
