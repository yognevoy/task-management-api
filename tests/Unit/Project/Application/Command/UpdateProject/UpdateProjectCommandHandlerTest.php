<?php

namespace App\Tests\Unit\Project\Application\Command\UpdateProject;

use App\Project\Application\Command\UpdateProject\UpdateProjectCommand;
use App\Project\Application\Command\UpdateProject\UpdateProjectCommandHandler;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Entity\Project;
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
class UpdateProjectCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private UpdateProjectCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private ProjectCacheManager|MockObject $projectCacheManager;
    private Project $existingProject;
    private User $existingUser;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->projectCacheManager = $this->createMock(ProjectCacheManager::class);

        $this->handler = new UpdateProjectCommandHandler(
            $this->entityManager,
            $this->projectRepository,
            $this->userRepository,
            $this->projectCacheManager
        );

        $this->existingProject = $this->createProjectWithId(1);
        $this->existingProject->setTitle('Old Title');
        $this->existingProject->setDescription('Old Description');
        $this->existingUser = $this->createUserWithId(1);
        $this->existingProject->setOwner($this->existingUser);
    }

    public function testHandlerShouldUpdateProjectSuccessfully(): void
    {
        $command = new UpdateProjectCommand(
            1,
            'New Title',
            'New Description',
            2 // New owner ID
        );

        $newOwner = $this->createUserWithId(2);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($newOwner);

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
        $this->assertEquals('New Title', $result->title);
        $this->assertEquals('New Description', $result->description);
        $this->assertEquals(2, $result->ownerId);
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new UpdateProjectCommand(999); // Non-existent project ID

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenOwnerDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateProjectCommand(
            1,
            null,
            null,
            999 // Non-existent user ID
        );

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

    public function testHandlerShouldUpdateOnlyTitleWhenOnlyTitleProvided(): void
    {
        $command = new UpdateProjectCommand(
            1,
            'Updated Title'
        );

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->never())
            ->method('find');

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
        $this->assertEquals('Updated Title', $result->title);
        $this->assertEquals('Old Description', $result->description);
        $this->assertEquals(1, $result->ownerId);
    }

    public function testHandlerShouldUpdateOnlyDescriptionWhenOnlyDescriptionProvided(): void
    {
        $command = new UpdateProjectCommand(
            1,
            null,
            'Updated Description'
        );

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->never())
            ->method('find');

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
        $this->assertEquals('Old Title', $result->title);
        $this->assertEquals('Updated Description', $result->description);
        $this->assertEquals(1, $result->ownerId);
    }

    public function testHandlerShouldUpdateOnlyOwnerWhenOnlyOwnerProvided(): void
    {
        $command = new UpdateProjectCommand(
            1,
            null,
            null,
            3 // New owner ID
        );

        $newOwner = $this->createUserWithId(3);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($newOwner);

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
        $this->assertEquals('Old Title', $result->title);
        $this->assertEquals('Old Description', $result->description);
        $this->assertEquals(3, $result->ownerId);
    }
}
