<?php

namespace App\User\Application\Query\GetAllUsers;

use App\Shared\Application\DTO\PaginatedResponse;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Application\DTO\UserListResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetAllUsersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private UserRepositoryInterface $userRepository,
        private TagAwareCacheInterface  $userCache,
    )
    {
    }

    public function __invoke(GetAllUsersQuery $query): PaginatedResponse
    {
        $pagination = $query->pagination;

        $cacheKey = sprintf(
            'users_all_page_%d_limit_%d',
            $pagination->getPage(),
            $pagination->getLimit()
        );

        return $this->userCache->get($cacheKey, function ($item) use ($pagination) {
            $item->tag(['users']);

            $qb = $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u');

            $total = $this->userRepository->countAll();

            $users = $qb
                ->orderBy('u.id', 'ASC')
                ->setFirstResult($pagination->getOffset())
                ->setMaxResults($pagination->getLimit())
                ->getQuery()
                ->getResult();

            $userListResponse = new UserListResponse($users);
            return new PaginatedResponse($userListResponse, $total, $pagination->getPage(), $pagination->getLimit());
        });
    }
}
