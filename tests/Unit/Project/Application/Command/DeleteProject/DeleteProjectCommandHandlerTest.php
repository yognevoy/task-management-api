<?php

namespace App\Tests\Unit\Project\Application\Command\DeleteProject;

use App\Project\Application\Command\DeleteProject\DeleteProjectCommand;
use App\Project\Application\Command\DeleteProject\DeleteProjectCommandHandler;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\ProjectHasTasksException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Tests\Trait\EntityFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeleteProjectCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private DeleteProjectCommandHandler $handler;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private ProjectCacheManager|MockObject $projectCacheManager;
    private Project $existingProject;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectCacheManager = $this->createMock(ProjectCacheManager::class);

        $this->handler = new DeleteProjectCommandHandler(
            $this->entityManager,
            $this->projectRepository,
            $this->taskRepository,
            $this->projectCacheManager
        );

        $this->existingProject = $this->createProjectWithId(1);
    }

    public function testHandlerShouldDeleteProjectSuccessfully(): void
    {
        $command = new DeleteProjectCommand(1);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->taskRepository
            ->expects($this->once())
            ->method('countByProject')
            ->with($this->equalTo($this->existingProject))
            ->willReturn(0);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->existingProject));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->projectCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingProject));

        ($this->handler)($command);

        $this->assertTrue(true);
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new DeleteProjectCommand(999); // Non-existent project ID

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowProjectHasTasksExceptionWhenProjectHasTasks(): void
    {
        $this->expectException(ProjectHasTasksException::class);

        $command = new DeleteProjectCommand(1);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->taskRepository
            ->expects($this->once())
            ->method('countByProject')
            ->with($this->equalTo($this->existingProject))
            ->willReturn(5); // Project has 5 tasks

        ($this->handler)($command);
    }
}
