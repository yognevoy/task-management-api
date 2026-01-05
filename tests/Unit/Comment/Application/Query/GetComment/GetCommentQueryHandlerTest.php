<?php

namespace App\Tests\Unit\Comment\Application\Query\GetComment;

use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Application\Query\GetComment\GetCommentQuery;
use App\Comment\Application\Query\GetComment\GetCommentQueryHandler;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Task\Domain\Entity\Task;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
class GetCommentQueryHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private GetCommentQueryHandler $handler;
    private CommentRepositoryInterface|MockObject $commentRepository;
    private CacheInterface|MockObject $commentCache;
    private User $currentUser;
    private User $taskOwner;
    private Task $task;
    private Comment $existingComment;

    protected function setUp(): void
    {
        $this->commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $this->commentCache = $this->createMock(CacheInterface::class);

        $this->handler = new GetCommentQueryHandler(
            $this->commentRepository,
            $this->commentCache
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingComment = $this->createCommentWithId(1);
        $this->existingComment->setContent('Test Comment Content');
        $this->existingComment->setAuthor($this->currentUser);
        $this->taskOwner = $this->createUserWithId(2);
        $this->taskOwner->setEmail('task_owner@example.com');
        $this->task = $this->createTaskWithId(1);
        $this->task->setOwner($this->taskOwner);
        $this->existingComment->setTask($this->task);
    }

    public function testHandlerShouldReturnCommentSuccessfully(): void
    {
        $query = new GetCommentQuery(1);

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingComment);

        $this->commentCache
            ->expects($this->once())
            ->method('get')
            ->with('comment_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = ($this->handler)($query);

        $this->assertInstanceOf(CommentResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Comment Content', $result->content);
    }

    public function testHandlerShouldThrowCommentNotFoundExceptionWhenCommentDoesNotExist(): void
    {
        $this->expectException(CommentNotFoundException::class);

        $query = new GetCommentQuery(999); // Non-existent comment ID

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->commentCache
            ->expects($this->once())
            ->method('get')
            ->with('comment_999')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        ($this->handler)($query);
    }
}
