<?php

namespace App\User\Infrastructure\Cache;

use App\User\Domain\Entity\User;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserCacheManager
{
    public function __construct(
        private TagAwareCacheInterface $userCache,
    )
    {
    }

    /**
     * Invalidates cache for a given user.
     *
     * @param User $user
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(User $user): void
    {
        $this->userCache->invalidateTags(['users']);
        $this->userCache->delete('user_' . $user->getId());
    }
}
