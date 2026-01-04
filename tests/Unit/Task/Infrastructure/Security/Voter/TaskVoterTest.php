<?php

namespace App\Tests\Unit\Task\Infrastructure\Security\Voter;

use App\Project\Domain\Entity\Project;
use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TaskVoter();
    }

    public function testAdminUserCanViewTask(): void
    {
        $adminUser = $this->createUserWithId(1, 'admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2, 'owner@example.com');
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
        $adminUser = $this->createUserWithId(1, 'admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2, 'owner@example.com');
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
        $adminUser = $this->createUserWithId(1, 'admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $task = $this->createTaskWithId(1);
        $owner = $this->createUserWithId(2, 'owner@example.com');
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
        $ownerUser = $this->createUserWithId(1, 'owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($ownerUser);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($ownerUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanEditOwnTask(): void
    {
        $ownerUser = $this->createUserWithId(1, 'owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($ownerUser);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($ownerUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanDeleteOwnTask(): void
    {
        $ownerUser = $this->createUserWithId(1, 'owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($ownerUser);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($ownerUser);

        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanViewTask(): void
    {
        $projectOwner = $this->createUserWithId(1, 'project_owner@example.com');
        $taskOwner = $this->createUserWithId(2, 'task_owner@example.com');

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
        $projectOwner = $this->createUserWithId(1, 'project_owner@example.com');
        $taskOwner = $this->createUserWithId(2, 'task_owner@example.com');

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
        $projectOwner = $this->createUserWithId(1, 'project_owner@example.com');
        $taskOwner = $this->createUserWithId(2, 'task_owner@example.com');

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
        $otherUser = $this->createUserWithId(1, 'other@example.com');
        $taskOwner = $this->createUserWithId(2, 'task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $projectOwner = $this->createUserWithId(3, 'project_owner@example.com');
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

    private function createUserWithId(int $id, string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);

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
