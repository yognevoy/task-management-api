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
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetAllProjectsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private TagAwareCacheInterface     $projectCache,
    )
    {
    }

    public function __invoke(GetAllProjectsQuery $query): PaginatedResponse
    {
        $pagination = $query->pagination;
        $ownerId = $query->ownerId;

        $cacheKey = $this->generateCacheKey($ownerId, $pagination);

        return $this->projectCache->get($cacheKey, function ($item) use ($query, $pagination) {
            $ownerId = $query->ownerId;

            $qb = $this->entityManager->createQueryBuilder()
                ->select('p')
                ->from(Project::class, 'p');

            if ($ownerId) {
                $item->tag(['user_' . $ownerId]);

                $user = $this->userRepository->find($ownerId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                $qb->where('p.owner = :user')
                    ->setParameter('user', $user);

                $total = $this->projectRepository->countByOwner($user);
            } else {
                $item->tag(['projects']);
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
        });
    }

    private function generateCacheKey(int $ownerId, $pagination): string
    {
        if ($ownerId) {
            return sprintf(
                'projects_user_%d_page_%d_limit_%d',
                $ownerId,
                $pagination->getPage(),
                $pagination->getLimit()
            );
        } else {
            return sprintf(
                'projects_all_page_%d_limit_%d',
                $pagination->getPage(),
                $pagination->getLimit()
            );
        }
    }
}
