<?php

namespace App\User\Application\Query\GetAllUsers;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Application\DTO\UserListResponse;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetAllUsersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CacheInterface          $userCache,
    )
    {
    }

    public function __invoke(GetAllUsersQuery $query): UserListResponse
    {
        $cacheKey = 'users_all';

        return $this->userCache->get($cacheKey, function () {
            $users = $this->userRepository->findAll();

            return new UserListResponse($users);
        });
    }
}
