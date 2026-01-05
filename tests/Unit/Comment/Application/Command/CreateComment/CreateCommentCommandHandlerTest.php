<?php

namespace App\Tests\Unit\Comment\Application\Command\CreateComment;

use App\Comment\Application\Command\CreateComment\CreateCommentCommand;
use App\Comment\Application\Command\CreateComment\CreateCommentCommandHandler;
use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CreateCommentCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private CreateCommentCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private CommentCacheManager|MockObject $commentCacheManager;
    private User $currentUser;
    private Task $existingTask;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->commentCacheManager = $this->createMock(CommentCacheManager::class);

        $this->handler = new CreateCommentCommandHandler(
            $this->entityManager,
            $this->taskRepository,
            $this->commentCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingTask = $this->createTaskWithId(1);
    }

    public function testHandlerShouldCreateCommentSuccessfully(): void
    {
        $command = new CreateCommentCommand(
            'Test Comment Content',
            1,
            $this->currentUser
        );

        $comment = null;
        $persistCallback = function ($persistedComment) use (&$comment) {
            $comment = $persistedComment;
        };

        $flushCallback = function () use (&$comment) {
            if ($comment !== null) {
                $reflection = new \ReflectionClass($comment);
                $property = $reflection->getProperty('id');
                $property->setValue($comment, 1);
            }
        };

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Comment::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->commentCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($comment) {
                return $comment instanceof Comment && $comment->getId() === 1;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(CommentResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Comment Content', $result->content);
        $this->assertEquals($this->currentUser->getId(), $result->authorId);
        $this->assertEquals(1, $result->taskId);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenCurrentUserIsNotUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $command = new CreateCommentCommand(
            'Test Comment Content',
            1,
            null // No current user
        );

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowTaskNotFoundExceptionWhenTaskDoesNotExist(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $command = new CreateCommentCommand(
            'Test Comment Content',
            999, // Non-existent task ID
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }
}
