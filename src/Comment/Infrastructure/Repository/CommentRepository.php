<?php

namespace App\Comment\Infrastructure\Repository;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository implements CommentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find all comments by task
     *
     * @return Comment[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find all comments by author
     *
     * @return Comment[]
     */
    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.author = :author')
            ->setParameter('author', $author)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
