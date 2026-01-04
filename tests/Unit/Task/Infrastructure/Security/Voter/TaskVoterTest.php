<?php

namespace App\Tests\Unit\Task\Infrastructure\Security\Voter;

use App\Task\Infrastructure\Security\Voter\TaskVoter;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoterTest extends TestCase
{
    use EntityFactoryTrait;

    private TaskVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TaskVoter();
    }

    public function testAdminUserCanViewTask(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanEditTask(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanDeleteTask(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanViewOwnTask(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanEditOwnTask(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanDeleteOwnTask(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanViewTask(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectOwner);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCannotEditTask(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectOwner);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testProjectOwnerCannotDeleteTask(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectOwner);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testOtherUserCannotAccessTask(): void
    {
        $otherUser = $this->createUserWithId(1);
        $otherUser->setEmail('other@example.com');

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $projectOwner = $this->createUserWithId(3);
        $projectOwner->setEmail('project_owner@example.com');
        $project->setOwner($projectOwner);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($otherUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }
}
