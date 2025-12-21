<?php

namespace App\Project\Infrastructure\Repository;

use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository implements ProjectRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Find all projects by owner
     *
     * @return Project[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Count tasks associated with a project
     */
    public function countTasks(Project $project): int
    {
        $result = $this->getEntityManager()
            ->createQuery('
                SELECT COUNT(t.id)
                FROM App\Task\Domain\Entity\Task t
                WHERE t.project = :project
            ')
            ->setParameter('project', $project)
            ->getSingleScalarResult();

        return (int)$result;
    }
}
