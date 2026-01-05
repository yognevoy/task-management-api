<?php

namespace App\Tests\Unit\Comment\Application\Command\DeleteComment;

use App\Comment\Application\Command\DeleteComment\DeleteCommentCommand;
use App\Comment\Application\Command\DeleteComment\DeleteCommentCommandHandler;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Cache\CommentCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeleteCommentCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private DeleteCommentCommandHandler $handler;
    private CommentRepositoryInterface|MockObject $commentRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private CommentCacheManager|MockObject $commentCacheManager;
    private Comment $existingComment;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->commentRepository = $this->createMock(CommentRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->commentCacheManager = $this->createMock(CommentCacheManager::class);

        $this->handler = new DeleteCommentCommandHandler(
            $this->entityManager,
            $this->commentRepository,
            $this->commentCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingComment = $this->createCommentWithId(1);
        $this->existingComment->setAuthor($this->currentUser);
    }

    public function testHandlerShouldDeleteCommentSuccessfully(): void
    {
        $command = new DeleteCommentCommand(1);

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingComment);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->existingComment));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->commentCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingComment));

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowCommentNotFoundExceptionWhenCommentDoesNotExist(): void
    {
        $this->expectException(CommentNotFoundException::class);

        $command = new DeleteCommentCommand(999); // Non-existent comment ID

        $this->commentRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }
}
