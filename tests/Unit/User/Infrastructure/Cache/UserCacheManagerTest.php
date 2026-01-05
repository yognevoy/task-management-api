<?php

namespace App\Tests\Unit\User\Infrastructure\Cache;

use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Cache\UserCacheManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class UserCacheManagerTest extends TestCase
{
    use EntityFactoryTrait;

    private UserCacheManager $cacheManager;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheManager = new UserCacheManager($this->cache);
    }

    public function testInvalidateCacheShouldDeleteCacheForKey(): void
    {
        $userId = 1;
        $user = $this->createUserWithId($userId);
        $cacheKey = 'user_' . $userId;

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cacheManager->invalidateCache($user);
    }

    public function testInvalidateCacheShouldHandleUserWithNullId(): void
    {
        $user = new User();

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('user_')
            ->willReturn(true);

        $this->cacheManager->invalidateCache($user);
    }
}
