<?php

namespace App\Project\Application\Query\GetAllProjects;

use App\Project\Application\DTO\ProjectListResponse;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\DTO\PaginatedResponse;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class GetAllProjectsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
    )
    {
    }

    public function __invoke(GetAllProjectsQuery $query): PaginatedResponse
    {
        $pagination = $query->pagination;
        $ownerId = $query->ownerId;

        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p');

        if ($ownerId) {
            $user = $this->userRepository->find($ownerId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            $qb->where('p.owner = :user')
                ->setParameter('user', $user);

            $total = $this->projectRepository->countByOwner($user);
        } else {
            $total = $this->projectRepository->countAll();
        }

        $projects = $qb
            ->orderBy('p.id', 'ASC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->getQuery()
            ->getResult();

        $projectListResponse = new ProjectListResponse($projects);
        return new PaginatedResponse($projectListResponse, $total, $pagination->getPage(), $pagination->getLimit());
    }
}
