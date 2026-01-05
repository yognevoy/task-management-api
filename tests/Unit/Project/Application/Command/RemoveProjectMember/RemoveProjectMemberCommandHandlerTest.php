<?php

namespace App\Tests\Unit\Project\Application\Command\RemoveProjectMember;

use App\Project\Application\Command\RemoveProjectMember\RemoveProjectMemberCommand;
use App\Project\Application\Command\RemoveProjectMember\RemoveProjectMemberCommandHandler;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\InvalidMemberException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RemoveProjectMemberCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private RemoveProjectMemberCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private ProjectCacheManager|MockObject $projectCacheManager;
    private Project $existingProject;
    private User $existingUser;
    private User $projectOwner;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->projectCacheManager = $this->createMock(ProjectCacheManager::class);

        $this->handler = new RemoveProjectMemberCommandHandler(
            $this->entityManager,
            $this->projectRepository,
            $this->userRepository,
            $this->projectCacheManager
        );

        $this->existingProject = $this->createProjectWithId(1);
        $this->existingProject->setTitle('Project title');
        $this->projectOwner = $this->createUserWithId(1);
        $this->existingProject->setOwner($this->projectOwner);
        $this->existingUser = $this->createUserWithId(2);
        $this->currentUser = $this->createUserWithId(3);
        $this->existingProject->addMember($this->existingUser);
    }

    public function testHandlerShouldRemoveMemberSuccessfullyWhenCurrentUserIsOwner(): void
    {
        $command = new RemoveProjectMemberCommand(1, 2, $this->projectOwner);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($this->existingUser);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->projectCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingProject));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(ProjectResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertFalse($this->existingProject->isMember($this->existingUser));
    }

    public function testHandlerShouldRemoveMemberSuccessfullyWhenCurrentUserIsSameUser(): void
    {
        $command = new RemoveProjectMemberCommand(1, 2, $this->existingUser);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($this->existingUser);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->projectCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingProject));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(ProjectResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertFalse($this->existingProject->isMember($this->existingUser));
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new RemoveProjectMemberCommand(999, 2, $this->currentUser); // Non-existent project ID

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new RemoveProjectMemberCommand(1, 999, $this->currentUser); // Non-existent user ID

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenCurrentUserIsNotOwnerOrSameUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $command = new RemoveProjectMemberCommand(1, 2, $this->currentUser);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($this->existingUser);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowInvalidMemberExceptionWhenRemovingOwner(): void
    {
        $this->expectException(InvalidMemberException::class);

        $command = new RemoveProjectMemberCommand(1, 1, $this->projectOwner);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->projectOwner);

        ($this->handler)($command);
    }
}
