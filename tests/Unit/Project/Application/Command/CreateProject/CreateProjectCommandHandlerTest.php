<?php

namespace App\Tests\Unit\Project\Application\Command\CreateProject;

use App\Project\Application\Command\CreateProject\CreateProjectCommand;
use App\Project\Application\Command\CreateProject\CreateProjectCommandHandler;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\Cache\ProjectCacheManager;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CreateProjectCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private CreateProjectCommandHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    private ProjectCacheManager|MockObject $projectCacheManager;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->projectCacheManager = $this->createMock(ProjectCacheManager::class);

        $this->handler = new CreateProjectCommandHandler(
            $this->entityManager,
            $this->projectCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
    }

    public function testHandlerShouldCreateProjectSuccessfully(): void
    {
        $command = new CreateProjectCommand(
            'Test Project',
            'Test Description',
            $this->currentUser
        );

        $project = null;
        $persistCallback = function ($persistedProject) use (&$project) {
            $project = $persistedProject;
        };

        $flushCallback = function () use (&$project) {
            if ($project !== null) {
                $reflection = new \ReflectionClass($project);
                $property = $reflection->getProperty('id');
                $property->setValue($project, 1);
            }
        };

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Project::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->projectCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($project) {
                return $project instanceof Project && $project->getId() === 1;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(ProjectResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Project', $result->title);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals($this->currentUser->getId(), $result->ownerId);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenCurrentUserIsNotUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $command = new CreateProjectCommand(
            'Test Project',
            null,
            null // No current user
        );

        ($this->handler)($command);
    }

    public function testHandlerShouldCreateProjectWithNullDescription(): void
    {
        $command = new CreateProjectCommand(
            'Test Project Without Description',
            null,
            $this->currentUser
        );

        $project = null;
        $persistCallback = function ($persistedProject) use (&$project) {
            $project = $persistedProject;
        };

        $flushCallback = function () use (&$project) {
            if ($project !== null) {
                $reflection = new \ReflectionClass($project);
                $property = $reflection->getProperty('id');
                $property->setValue($project, 2);
            }
        };

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Project::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->projectCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($project) {
                return $project instanceof Project && $project->getId() === 2;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(ProjectResponse::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('Test Project Without Description', $result->title);
        $this->assertNull($result->description);
        $this->assertEquals($this->currentUser->getId(), $result->ownerId);
    }
}
