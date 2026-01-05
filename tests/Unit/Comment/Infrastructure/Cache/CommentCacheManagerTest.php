<?php

namespace App\Tests\Unit\Comment\Infrastructure\Cache;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class CommentCacheManagerTest extends TestCase
{
    use EntityFactoryTrait;

    private CommentCacheManager $cacheManager;
    private CacheInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheManager = new CommentCacheManager($this->cache);
    }

    public function testInvalidateCacheShouldDeleteCacheForKey(): void
    {
        $commentId = 1;
        $comment = $this->createCommentWithId(1);
        $cacheKey = 'comment_' . $commentId;

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cacheManager->invalidateCache($comment);
    }

    public function testInvalidateCacheShouldHandleCommentWithNullId(): void
    {
        $comment = new Comment();

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with('comment_')
            ->willReturn(true);

        $this->cacheManager->invalidateCache($comment);
    }
}
