<?php

namespace App\Tests\Unit\User\Application\Command\DeleteUser;

use App\Tests\Trait\EntityFactoryTrait;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\DeleteUser\DeleteUserCommandHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserHasOwnedProjectsException;
use App\User\Domain\Exception\UserHasOwnedTasksException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeleteUserCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private DeleteUserCommandHandler $handler;
    private UserRepositoryInterface|MockObject $userRepository;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private UserCacheManager|MockObject $userCacheManager;
    private User $existingUser;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userCacheManager = $this->createMock(UserCacheManager::class);

        $this->handler = new DeleteUserCommandHandler(
            $this->entityManager,
            $this->userRepository,
            $this->taskRepository,
            $this->projectRepository,
            $this->userCacheManager
        );

        $this->existingUser = $this->createUserWithId(1);
    }

    public function testHandlerShouldDeleteUserSuccessfully(): void
    {
        $command = new DeleteUserCommand(1);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->taskRepository
            ->expects($this->once())
            ->method('countByOwner')
            ->with($this->equalTo($this->existingUser))
            ->willReturn(0);

        $this->projectRepository
            ->expects($this->once())
            ->method('countByOwner')
            ->with($this->equalTo($this->existingUser))
            ->willReturn(0);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->existingUser));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingUser));

        ($this->handler)($command);

        $this->assertTrue(true);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new DeleteUserCommand(999); // Non-existent user ID

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowUserHasOwnedTasksExceptionWhenUserHasOwnedTasks(): void
    {
        $this->expectException(UserHasOwnedTasksException::class);

        $command = new DeleteUserCommand(1);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->taskRepository
            ->expects($this->once())
            ->method('countByOwner')
            ->with($this->equalTo($this->existingUser))
            ->willReturn(5); // User has 5 owned tasks

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowUserHasOwnedProjectsExceptionWhenUserHasOwnedProjects(): void
    {
        $this->expectException(UserHasOwnedProjectsException::class);

        $command = new DeleteUserCommand(1);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->taskRepository
            ->expects($this->once())
            ->method('countByOwner')
            ->with($this->equalTo($this->existingUser))
            ->willReturn(0); // No owned tasks

        $this->projectRepository
            ->expects($this->once())
            ->method('countByOwner')
            ->with($this->equalTo($this->existingUser))
            ->willReturn(2); // User has 2 owned projects

        ($this->handler)($command);
    }
}
