<?php

namespace App\Tests\Unit\Task\Domain\Entity;

use App\Project\Domain\Entity\Project;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TaskEntityTest extends TestCase
{
    public function testTaskCanBeCreatedWithDefaultValues(): void
    {
        $task = new Task();

        $this->assertNull($task->getId());
        $this->assertEquals(TaskStatus::TODO, $task->getStatus());
        $this->assertEquals(TaskType::TASK, $task->getType());
        $this->assertEquals(TaskPriority::LOW, $task->getPriority());
        $this->assertNotNull($task->getCreatedAt());
        $this->assertNotNull($task->getUpdatedAt());
        $this->assertEmpty($task->getSubtasks());
        $this->assertEmpty($task->getComments());
    }

    public function testSetTitleShouldSetTitle(): void
    {
        $task = new Task();
        $title = 'Test Task';

        $task->setTitle($title);

        $this->assertEquals($title, $task->getTitle());
    }

    public function testSetDescriptionShouldSetDescription(): void
    {
        $task = new Task();
        $description = 'Test Description';

        $task->setDescription($description);

        $this->assertEquals($description, $task->getDescription());
    }

    public function testSetOwnerShouldSetOwner(): void
    {
        $task = new Task();
        $user = $this->createUserWithId(1);

        $task->setOwner($user);

        $this->assertEquals($user, $task->getOwner());
        $this->assertEquals(1, $task->getOwnerId());
    }

    public function testSetProjectShouldSetProject(): void
    {
        $task = new Task();
        $project = $this->createProjectWithId(1);

        $task->setProject($project);

        $this->assertEquals($project, $task->getProject());
        $this->assertEquals(1, $task->getProjectId());
    }

    public function testSetDueDateShouldSetDueDate(): void
    {
        $task = new Task();
        $dueDate = new DateTimeImmutable('+1 day');

        $task->setDueDate($dueDate);

        $this->assertEquals($dueDate, $task->getDueDate());
    }

    public function testSetParentShouldSetParentTask(): void
    {
        $task = new Task();
        $parentTask = $this->createTaskWithId(1);

        $task->setParent($parentTask);

        $this->assertEquals($parentTask, $task->getParent());
        $this->assertEquals(1, $task->getParentId());
    }

    public function testAddSubtaskShouldAddSubtask(): void
    {
        $task = new Task();
        $subtask = new Task();

        $task->addSubtask($subtask);

        $this->assertCount(1, $task->getSubtasks());
        $this->assertTrue($task->getSubtasks()->contains($subtask));
        $this->assertEquals($task, $subtask->getParent());
    }

    public function testRemoveSubtaskShouldRemoveSubtask(): void
    {
        $task = new Task();
        $subtask = new Task();
        $task->addSubtask($subtask);

        $task->removeSubtask($subtask);

        $this->assertCount(0, $task->getSubtasks());
        $this->assertFalse($task->getSubtasks()->contains($subtask));
        $this->assertNull($subtask->getParent());
    }

    public function testUpdateTimestampsShouldUpdateUpdatedAt(): void
    {
        $task = new Task();
        $initialUpdatedAt = $task->getUpdatedAt();

        usleep(100000);

        $task->updateTimestamps();
        $updatedUpdatedAt = $task->getUpdatedAt();

        $this->assertNotEquals($initialUpdatedAt, $updatedUpdatedAt);
    }

    public function testSetStatusShouldSetStatus(): void
    {
        $task = new Task();
        $status = TaskStatus::IN_PROGRESS;

        $task->setStatus($status);

        $this->assertEquals($status, $task->getStatus());
    }

    public function testSetTypeShouldSetType(): void
    {
        $task = new Task();
        $type = TaskType::BUG;

        $task->setType($type);

        $this->assertEquals($type, $task->getType());
    }

    public function testSetPriorityShouldSetPriority(): void
    {
        $task = new Task();
        $priority = TaskPriority::HIGH;

        $task->setPriority($priority);

        $this->assertEquals($priority, $task->getPriority());
    }

    private function createUserWithId(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setValue($user, $id);

        return $user;
    }

    private function createProjectWithId(int $id): Project
    {
        $project = new Project();
        $reflection = new \ReflectionClass($project);
        $property = $reflection->getProperty('id');
        $property->setValue($project, $id);

        return $project;
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
