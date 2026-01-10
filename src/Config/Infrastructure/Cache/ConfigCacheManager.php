<?php

namespace App\Config\Infrastructure\Cache;

use Symfony\Contracts\Cache\CacheInterface;

class ConfigCacheManager
{
    public function __construct(
        private CacheInterface $configCache,
    )
    {
    }

    /**
     * Invalidates the entire configuration cache.
     *
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(): void
    {
        $this->configCache->delete('configuration');
    }
}
