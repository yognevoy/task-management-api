<?php

namespace App\Tests\Unit\Task\Infrastructure\Cache;

use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class TaskCacheManagerTest extends TestCase
{
    use EntityFactoryTrait;

    private TaskCacheManager $cacheManager;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheManager = new TaskCacheManager($this->cache);
    }

    public function testInvalidateCacheShouldDeleteCacheForKey(): void
    {
        $taskId = 1;
        $task = $this->createTaskWithId(1);
        $cacheKey = 'task_' . $taskId;

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cacheManager->invalidateCache($task);
    }

    public function testInvalidateCacheShouldHandleTaskWithNullId(): void
    {
        $task = new Task();

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('task_')
            ->willReturn(true);

        $this->cacheManager->invalidateCache($task);
    }
}
