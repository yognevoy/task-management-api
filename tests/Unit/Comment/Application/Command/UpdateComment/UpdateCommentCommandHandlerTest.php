<?php

namespace App\Tests\Unit\Comment\Application\Command\UpdateComment;

use App\Comment\Application\Command\UpdateComment\UpdateCommentCommand;
use App\Comment\Application\Command\UpdateComment\UpdateCommentCommandHandler;
use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Task\Domain\Entity\Task;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UpdateCommentCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private UpdateCommentCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private CommentRepositoryInterface|MockObject $commentRepository;
    private CommentCacheManager|MockObject $commentCacheManager;
    private Comment $existingComment;
    private User $currentUser;
    private Task $task;
    private User $taskOwner;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $this->commentCacheManager = $this->createMock(CommentCacheManager::class);

        $this->handler = new UpdateCommentCommandHandler(
            $this->entityManager,
            $this->commentRepository,
            $this->commentCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->taskOwner = $this->createUserWithId(2);
        $this->task = $this->createTaskWithId(1);
        $this->task->setOwner($this->taskOwner);

        $this->existingComment = $this->createCommentWithId(1);
        $this->existingComment->setContent('Old Comment Content');
        $this->existingComment->setAuthor($this->currentUser);
        $this->existingComment->setTask($this->task);
    }

    public function testHandlerShouldUpdateCommentSuccessfully(): void
    {
        $command = new UpdateCommentCommand(
            1,
            'Updated Comment Content'
        );

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingComment);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->commentCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingComment));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(CommentResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Updated Comment Content', $result->content);
    }

    public function testHandlerShouldThrowCommentNotFoundExceptionWhenCommentDoesNotExist(): void
    {
        $this->expectException(CommentNotFoundException::class);

        $command = new UpdateCommentCommand(999); // Non-existent comment ID

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }
}
