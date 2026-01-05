<?php

namespace App\Tests\Unit\Task\Application\Command\DeleteTask;

use App\Task\Application\Command\DeleteTask\DeleteTaskCommand;
use App\Task\Application\Command\DeleteTask\DeleteTaskCommandHandler;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Cache\TaskCacheManager;
use App\Tests\Trait\EntityFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DeleteTaskCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private DeleteTaskCommandHandler $handler;
    private TaskRepositoryInterface|MockObject $taskRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private TaskCacheManager|MockObject $taskCacheManager;
    private Task $existingTask;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskCacheManager = $this->createMock(TaskCacheManager::class);

        $this->handler = new DeleteTaskCommandHandler(
            $this->taskRepository,
            $this->entityManager,
            $this->taskCacheManager
        );

        $this->existingTask = $this->createTaskWithId(1);
    }

    public function testHandlerShouldDeleteTaskSuccessfully(): void
    {
        $command = new DeleteTaskCommand(1);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingTask);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->existingTask));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->taskCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingTask));

        ($this->handler)($command);
    }

    public function testHandlerShouldThrowTaskNotFoundExceptionWhenTaskDoesNotExist(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $command = new DeleteTaskCommand(999); // Non-existent task ID

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }
}
