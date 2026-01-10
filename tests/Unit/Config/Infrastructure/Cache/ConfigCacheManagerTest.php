<?php

namespace App\Tests\Unit\Config\Infrastructure\Cache;

use App\Config\Infrastructure\Cache\ConfigCacheManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class ConfigCacheManagerTest extends TestCase
{
    private ConfigCacheManager $cacheManager;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheManager = new ConfigCacheManager($this->cache);
    }

    public function testInvalidateCacheShouldDeleteCache(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('configuration');

        $this->cacheManager->invalidateCache();
    }
}
