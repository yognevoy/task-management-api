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

    /**
     * Count comments by task
     *
     * @param Task $task
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByTask(Task $task): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count comments by author
     *
     * @param User $author
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByAuthor(User $author): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count comments accessible by user (author of comment, owner, assignee of task or owner, member of project)
     *
     * @param User $user
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.task', 't')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.members', 'm')
            ->where('c.author = :user OR t.owner = :user OR t.assignee = :user OR p.owner = :user OR m = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count all comments
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
