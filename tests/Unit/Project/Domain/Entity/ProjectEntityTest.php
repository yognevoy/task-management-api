<?php

namespace App\Tests\Unit\Project\Domain\Entity;

use App\Project\Domain\Entity\Project;
use App\Tests\Trait\EntityFactoryTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProjectEntityTest extends TestCase
{
    use EntityFactoryTrait;

    public function testProjectCanBeCreatedWithDefaultValues(): void
    {
        $project = new Project();

        $this->assertNull($project->getId());
        $this->assertNull($project->getTitle());
        $this->assertNull($project->getDescription());
        $this->assertNotNull($project->getCreatedAt());
        $this->assertNotNull($project->getUpdatedAt());
        $this->assertEmpty($project->getTasks());
        $this->assertEmpty($project->getMembers());
    }

    public function testSetTitleShouldSetTitle(): void
    {
        $project = new Project();
        $title = 'Test Project';

        $project->setTitle($title);

        $this->assertEquals($title, $project->getTitle());
    }

    public function testSetDescriptionShouldSetDescription(): void
    {
        $project = new Project();
        $description = 'Test Description';

        $project->setDescription($description);

        $this->assertEquals($description, $project->getDescription());
    }

    public function testSetOwnerShouldSetOwner(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);

        $project->setOwner($user);

        $this->assertEquals($user, $project->getOwner());
        $this->assertEquals(1, $project->getOwnerId());
    }

    public function testIsOwnerShouldReturnTrueWhenUserIsOwner(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);

        $project->setOwner($user);

        $this->assertTrue($project->isOwner($user));
    }

    public function testIsOwnerShouldReturnFalseWhenUserIsNotOwner(): void
    {
        $project = new Project();
        $owner = $this->createUserWithId(1);
        $otherUser = $this->createUserWithId(2);

        $project->setOwner($owner);

        $this->assertFalse($project->isOwner($otherUser));
    }

    public function testAddTaskShouldAddTask(): void
    {
        $project = new Project();
        $task = $this->createTaskWithId(1);

        $project->addTask($task);

        $this->assertCount(1, $project->getTasks());
        $this->assertTrue($project->getTasks()->contains($task));
        $this->assertEquals($project, $task->getProject());
    }

    public function testRemoveTaskShouldRemoveTask(): void
    {
        $project = new Project();
        $task = $this->createTaskWithId(1);
        $project->addTask($task);

        $project->removeTask($task);

        $this->assertCount(0, $project->getTasks());
        $this->assertFalse($project->getTasks()->contains($task));
        $this->assertNull($task->getProject());
    }

    public function testUpdateTimestampsShouldUpdateUpdatedAt(): void
    {
        $project = new Project();
        $initialUpdatedAt = $project->getUpdatedAt();

        usleep(100000);

        $project->updateTimestamps();
        $updatedUpdatedAt = $project->getUpdatedAt();

        $this->assertNotEquals($initialUpdatedAt, $updatedUpdatedAt);
    }

    public function testAddMemberShouldAddMember(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);

        $project->addMember($user);

        $this->assertCount(1, $project->getMembers());
        $this->assertTrue($project->getMembers()->contains($user));
    }

    public function testRemoveMemberShouldRemoveMember(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);
        $project->addMember($user);

        $project->removeMember($user);

        $this->assertCount(0, $project->getMembers());
        $this->assertFalse($project->getMembers()->contains($user));
    }

    public function testIsMemberShouldReturnTrueWhenUserIsMember(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);
        $project->addMember($user);

        $this->assertTrue($project->isMember($user));
    }

    public function testIsMemberShouldReturnFalseWhenUserIsNotMember(): void
    {
        $project = new Project();
        $user = $this->createUserWithId(1);

        $this->assertFalse($project->isMember($user));
    }

    public function testSetCreatedAtShouldSetCreatedAt(): void
    {
        $project = new Project();
        $createdAt = new DateTimeImmutable();

        $project->setCreatedAt($createdAt);

        $this->assertEquals($createdAt, $project->getCreatedAt());
    }

    public function testSetUpdatedAtShouldSetUpdatedAt(): void
    {
        $project = new Project();
        $updatedAt = new DateTimeImmutable();

        $project->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $project->getUpdatedAt());
    }
}
