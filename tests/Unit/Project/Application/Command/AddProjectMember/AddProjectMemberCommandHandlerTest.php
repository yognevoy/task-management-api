<?php

namespace App\Tests\Unit\Project\Application\Command\AddProjectMember;

use App\Project\Application\Command\AddProjectMember\AddProjectMemberCommand;
use App\Project\Application\Command\AddProjectMember\AddProjectMemberCommandHandler;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\InvalidMemberException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AddProjectMemberCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private AddProjectMemberCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private ProjectCacheManager|MockObject $projectCacheManager;
    private Project $existingProject;
    private User $existingUser;
    private User $projectOwner;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->projectCacheManager = $this->createMock(ProjectCacheManager::class);

        $this->handler = new AddProjectMemberCommandHandler(
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
    }

    public function testHandlerShouldAddMemberSuccessfully(): void
    {
        $command = new AddProjectMemberCommand(1, 2);

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
        $this->assertTrue($this->existingProject->isMember($this->existingUser));
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new AddProjectMemberCommand(999, 2); // Non-existent project ID

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

        $command = new AddProjectMemberCommand(1, 999); // Non-existent user ID

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

    public function testHandlerShouldThrowInvalidMemberExceptionWhenAddingOwnerAsMember(): void
    {
        $this->expectException(InvalidMemberException::class);

        $command = new AddProjectMemberCommand(1, 1); // Trying to add owner as member

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
