<?php

namespace App\Comment\Domain\Repository;

use App\Comment\Domain\Entity\Comment;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;

interface CommentRepositoryInterface
{
    /**
     * @return Comment|null
     */
    public function find($id, $lockMode = null, $lockVersion = null);

    /**
     * @return Comment[]
     */
    public function findAll();

    /**
     * @return Comment[]
     */
    public function findByTask(Task $task): array;

    /**
     * @return Comment[]
     */
    public function findByAuthor(User $author): array;

    /**
     * @return array[]
     */
    public function findRelatedUsersByComment(int $commentId): array;

    /**
     * @return int
     */
    public function countByTask(Task $task): int;

    /**
     * @return int
     */
    public function countByAuthor(User $author): int;

    /**
     * @return int
     */
    public function countByUser(User $user): int;

    /**
     * @return int
     */
    public function countAll(): int;
}
