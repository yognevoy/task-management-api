<?php

namespace App\Tests\Unit\Task\Application\Command\CreateTask;

use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\Command\CreateTask\CreateTaskCommand;
use App\Task\Application\Command\CreateTask\CreateTaskCommandHandler;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CreateTaskCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private CreateTaskCommandHandler $handler;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private TaskCacheManager|MockObject $taskCacheManager;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskCacheManager = $this->createMock(TaskCacheManager::class);

        $this->handler = new CreateTaskCommandHandler(
            $this->taskRepository,
            $this->userRepository,
            $this->projectRepository,
            $this->entityManager,
            $this->taskCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
    }

    public function testHandlerShouldCreateTaskSuccessfully(): void
    {
        $command = new CreateTaskCommand(
            'Test Task',
            'Test Description',
            'todo',
            'task',
            'low',
            null,
            null,
            null,
            null,
            $this->currentUser
        );

        $task = null;
        $persistCallback = function ($persistedTask) use (&$task) {
            $task = $persistedTask;
        };

        $flushCallback = function () use (&$task) {
            if ($task !== null) {
                $reflection = new \ReflectionClass($task);
                $property = $reflection->getProperty('id');
                $property->setValue($task, 1);
            }
        };

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Task::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->taskCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($task) {
                return $task instanceof Task && $task->getId() === 1;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Task', $result->title);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals($this->currentUser->getId(), $result->ownerId);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenCurrentUserIsNotUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $command = new CreateTaskCommand(
            'Test Task',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null // No current user
        );

        ($this->handler)($command);
    }

    public function testHandlerShouldSetProjectWhenProjectIdProvided(): void
    {
        $projectId = 1;
        $project = $this->createProjectWithId($projectId);
        $project->setOwner($this->currentUser);

        $command = new CreateTaskCommand(
            'Test Task',
            null,
            null,
            null,
            null,
            null,
            null,
            $projectId,
            null,
            $this->currentUser
        );

        $task = null;
        $persistCallback = function ($persistedTask) use (&$task) {
            $task = $persistedTask;
        };

        $flushCallback = function () use (&$task) {
            if ($task !== null) {
                $reflection = new \ReflectionClass($task);
                $property = $reflection->getProperty('id');
                $property->setValue($task, 1);
            }
        };

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with($projectId)
            ->willReturn($project);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Task::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($projectId, $result->projectId);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenUserIsNotProjectOwner(): void
    {
        $this->expectException(AccessDeniedException::class);

        $projectId = 1;
        $project = $this->createProjectWithId($projectId);
        $otherUser = $this->createUserWithId(999);
        $project->setOwner($otherUser);

        $command = new CreateTaskCommand(
            'Test Task',
            null,
            null,
            null,
            null,
            null,
            null,
            $projectId,
            null,
            $this->currentUser
        );

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($project);

        ($this->handler)($command);
    }
}
