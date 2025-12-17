<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Find all tasks by owner
     *
     * @return Task[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find all subtasks by parent task
     *
     * @return Task[]
     */
    public function findByParent(Task $parent): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
