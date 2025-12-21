<?php

namespace App\Comment\Domain\Repository;

use App\Comment\Domain\Entity\Comment;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;

interface CommentRepositoryInterface
{
    public function find($id, $lockMode = null, $lockVersion = null);

    public function findAll();

    /**
     * @return Comment[]
     */
    public function findByTask(Task $task): array;

    /**
     * @return Comment[]
     */
    public function findByAuthor(User $author): array;
}
