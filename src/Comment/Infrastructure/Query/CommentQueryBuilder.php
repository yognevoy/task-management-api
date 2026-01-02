<?php

namespace App\Comment\Infrastructure\Query;

use App\Comment\Domain\Entity\Comment;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;
use Doctrine\ORM\QueryBuilder;

class CommentQueryBuilder
{
    public function buildForUser(QueryBuilder $qb, User $user): QueryBuilder
    {
        return $qb
            ->select('c')
            ->from(Comment::class, 'c')
            ->join('c.task', 't')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.members', 'm')
            ->where('c.author = :user OR t.owner = :user OR t.assignee = :user OR p.owner = :user OR m = :user')
            ->setParameter('user', $user);
    }

    public function buildForAdmin(QueryBuilder $qb): QueryBuilder
    {
        return $qb
            ->select('c')
            ->from(Comment::class, 'c');
    }

    public function buildByTask(QueryBuilder $qb, Task $task): QueryBuilder
    {
        return $qb
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.task = :task')
            ->setParameter('task', $task);
    }

    public function buildByAuthor(QueryBuilder $qb, User $author): QueryBuilder
    {
        return $qb
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.author = :author')
            ->setParameter('author', $author);
    }
}
