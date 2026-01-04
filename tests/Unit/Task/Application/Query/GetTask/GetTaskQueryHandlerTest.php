<?php

namespace App\Tests\Unit\Task\Application\Query\GetTask;

use App\Task\Application\DTO\TaskResponse;
use App\Task\Application\Query\GetTask\GetTaskQuery;
use App\Task\Application\Query\GetTask\GetTaskQueryHandler;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
class GetTaskQueryHandlerTest extends TestCase
{
    private GetTaskQueryHandler $handler;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private CacheInterface|MockObject $taskCache;
    private User $currentUser;
    private Task $existingTask;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->taskCache = $this->createMock(CacheInterface::class);

        $this->handler = new GetTaskQueryHandler(
            $this->taskRepository,
            $this->taskCache
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingTask = $this->createTaskWithId(1);
        $this->existingTask->setTitle('Test Task');
        $this->existingTask->setOwner($this->currentUser);
    }

    public function testHandlerShouldReturnTaskSuccessfully(): void
    {
        $query = new GetTaskQuery(1, $this->currentUser);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->taskCache
            ->expects($this->once())
            ->method('get')
            ->with('task_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = ($this->handler)($query);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Task', $result->title);
    }

    public function testHandlerShouldThrowTaskNotFoundExceptionWhenTaskDoesNotExist(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $query = new GetTaskQuery(999, $this->currentUser); // Non-existent task ID

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->taskCache
            ->expects($this->once())
            ->method('get')
            ->with('task_999')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        ($this->handler)($query);
    }

    public function testHandlerShouldWorkWithNullCurrentUser(): void
    {
        $query = new GetTaskQuery(1, null); // No current user

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->taskCache
            ->expects($this->once())
            ->method('get')
            ->with('task_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = ($this->handler)($query);

        $this->assertInstanceOf(TaskResponse::class, $result);
        $this->assertEquals(1, $result->id);
    }

    private function createUserWithId(int $id): User
    {
        $user = new User();

        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setValue($user, $id);

        return $user;
    }

    private function createTaskWithId(int $id): Task
    {
        $task = new Task();

        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('id');
        $property->setValue($task, $id);

        return $task;
    }
}
