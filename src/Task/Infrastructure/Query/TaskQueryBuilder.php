<?php

namespace App\Task\Infrastructure\Query;

use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;
use Doctrine\ORM\QueryBuilder;

class TaskQueryBuilder
{
    public function buildForUser(QueryBuilder $qb, User $user): QueryBuilder
    {
        return $qb
            ->select('t')
            ->from(Task::class, 't')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.members', 'm')
            ->where('t.owner = :user OR t.assignee = :user OR p.owner = :user OR m = :user')
            ->setParameter('user', $user);
    }

    public function buildForAdmin(QueryBuilder $qb): QueryBuilder
    {
        return $qb
           ->select('t')
            ->from(Task::class, 't');
    }
}
