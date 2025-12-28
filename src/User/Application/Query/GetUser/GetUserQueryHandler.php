<?php

namespace App\User\Application\Query\GetUser;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CacheInterface          $userCache,
    )
    {
    }

    public function __invoke(GetUserQuery $query): UserResponse
    {
        $cacheKey = 'user_' . $query->id;

        return $this->userCache->get($cacheKey, function () use ($query) {
            $user = $this->userRepository->find($query->id);
            if (!$user) {
                throw new UserNotFoundException();
            }

            return UserResponse::fromEntity($user);
        });
    }
}
