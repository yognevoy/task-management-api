<?php

namespace App\Tests\Unit\Project\Infrastructure\Cache;

use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class ProjectCacheManagerTest extends TestCase
{
    use EntityFactoryTrait;

    private ProjectCacheManager $cacheManager;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheManager = new ProjectCacheManager($this->cache);
    }

    public function testInvalidateCacheShouldDeleteCacheForKey(): void
    {
        $projectId = 1;
        $project = $this->createProjectWithId(1);
        $cacheKey = 'project_' . $projectId;

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cacheManager->invalidateCache($project);
    }

    public function testInvalidateCacheShouldHandleProjectWithNullId(): void
    {
        $project = new Project();

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('project_')
            ->willReturn(true);

        $this->cacheManager->invalidateCache($project);
    }
}
