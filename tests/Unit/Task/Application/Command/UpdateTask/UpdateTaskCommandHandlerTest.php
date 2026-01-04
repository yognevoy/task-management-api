<?php

namespace App\Tests\Unit\Task\Application\Command\UpdateTask;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Task\Application\Command\UpdateTask\UpdateTaskCommandHandler;
use App\Task\Application\DTO\TaskResponse;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\Task\Domain\Exception\CircularTaskReferenceException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UpdateTaskCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private UpdateTaskCommandHandler $handler;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private TaskCacheManager|MockObject $taskCacheManager;
    private User $currentUser;
    private Task $existingTask;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskCacheManager = $this->createMock(TaskCacheManager::class);

        $this->handler = new UpdateTaskCommandHandler(
            $this->taskRepository,
            $this->userRepository,
            $this->projectRepository,
            $this->entityManager,
            $this->taskCacheManager
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingTask = $this->createTaskWithId(1);
        $this->existingTask->setTitle('Title');
        $this->existingTask->setOwner($this->currentUser);
    }

    public function testHandlerShouldUpdateTaskSuccessfully(): void
    {
        $command = new UpdateTaskCommand(
            1,
            'Updated Task',
            'Updated Description',
            'in_progress',
            'feature',
            'high',
            '2025-12-31T23:59:59+00:00',
            null,
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->taskCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingTask));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Updated Task', $result->title);
        $this->assertEquals('Updated Description', $result->description);
        $this->assertEquals('in_progress', $result->status);
        $this->assertEquals('feature', $result->type);
        $this->assertEquals('high', $result->priority);
        $this->assertNotNull($result->dueDate);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenCurrentUserIsNotUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $command = new UpdateTaskCommand(
            1,
            null,
            null,
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

    public function testHandlerShouldThrowTaskNotFoundExceptionWhenTaskDoesNotExist(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $command = new UpdateTaskCommand(
            999, // Non-existent task ID
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldUpdateOnlyProvidedFields(): void
    {
        $command = new UpdateTaskCommand(
            1,
            'Updated Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->currentUser
        );

        $this->existingTask->setTitle('Title');
        $this->existingTask->setDescription('Original Description');
        $this->existingTask->setStatus(TaskStatus::TODO);
        $this->existingTask->setType(TaskType::TASK);
        $this->existingTask->setPriority(TaskPriority::LOW);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals('Updated Title', $result->title);
        $this->assertEquals('Original Description', $result->description);
        $this->assertEquals('todo', $result->status);
        $this->assertEquals('task', $result->type);
        $this->assertEquals('low', $result->priority);
    }

    public function testHandlerShouldSetProjectWhenProjectIdProvided(): void
    {
        $projectId = 1;
        $project = $this->createProjectWithId($projectId);
        $project->setOwner($this->currentUser);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            $projectId,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with($projectId)
            ->willReturn($project);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($projectId, $result->projectId);
    }

    public function testHandlerShouldSetProjectToNullWhenProjectIdIsZero(): void
    {
        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            0, // Set project to null
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertNull($result->projectId);
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            999, // Non-existent project ID
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowAccessDeniedExceptionWhenUserIsNotProjectOwner(): void
    {
        $this->expectException(AccessDeniedException::class);

        $projectId = 1;
        $project = $this->createProjectWithId($projectId);
        $otherUser = $this->createUserWithId(999);
        $project->setOwner($otherUser);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            $projectId,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($project);

        ($this->handler)($command);
    }

    public function testHandlerShouldSetAssigneeWhenAssigneeIdProvided(): void
    {
        $assigneeId = 2;
        $assignee = $this->createUserWithId($assigneeId);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $assigneeId,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($assigneeId)
            ->willReturn($assignee);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($assigneeId, $result->assigneeId);
    }

    public function testHandlerShouldSetAssigneeToNullWhenAssigneeIdIsZero(): void
    {
        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            0, // Set assignee to null
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertNull($result->assigneeId);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenAssigneeDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            999, // Non-existent user ID
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldSetParentWhenParentIdProvided(): void
    {
        $parentId = 2;
        $parentTask = $this->createTaskWithId($parentId);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            $parentId,
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [1, $this->existingTask],
                [$parentId, $parentTask],
            ]);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($parentId, $result->parentId);
    }

    public function testHandlerShouldSetParentToNullWhenParentIdIsZero(): void
    {
        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            0, // Set parent to null
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertNull($result->parentId);
    }

    public function testHandlerShouldThrowParentTaskNotFoundExceptionWhenParentDoesNotExist(): void
    {
        $this->expectException(ParentTaskNotFoundException::class);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            999, // Non-existent parent task ID
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [1, $this->existingTask],
                [999, null],
            ]);

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowCircularTaskReferenceExceptionWhenTaskIsItsOwnParent(): void
    {
        $this->expectException(CircularTaskReferenceException::class);

        $command = new UpdateTaskCommand(
            1, // Task ID
            'Title',
            null,
            null,
            null,
            null,
            null,
            1, // Same ID as the task being updated
            null,
            null,
            null,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [1, $this->existingTask],
                [1, $this->existingTask], // Same task returned for both calls
            ]);

        ($this->handler)($command);
    }

    public function testHandlerShouldUpdateOwnerWhenOwnerIdProvided(): void
    {
        $newOwnerId = 2;
        $newOwner = $this->createUserWithId($newOwnerId);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $newOwnerId,
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($newOwnerId)
            ->willReturn($newOwner);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals($newOwnerId, $result->ownerId);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenOwnerDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateTaskCommand(
            1,
            'Title',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            999, // Non-existent user ID
            $this->currentUser
        );

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }
}
