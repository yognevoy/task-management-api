<?php

namespace App\Tests\Unit\Project\Infrastructure\Security\Voter;

use App\Project\Infrastructure\Security\Voter\ProjectVoter;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoterTest extends TestCase
{
    use EntityFactoryTrait;

    private ProjectVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ProjectVoter();
    }

    public function testAdminUserCanViewProject(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $project = $this->createProjectWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $project, [ProjectVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanEditProject(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $project = $this->createProjectWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $project, [ProjectVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanDeleteProject(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $project = $this->createProjectWithId(1);
        $owner = $this->createUserWithId(2);
        $owner->setEmail('owner@example.com');
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $project, [ProjectVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanViewOwnProject(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $project, [ProjectVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanEditOwnProject(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $project, [ProjectVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanDeleteOwnProject(): void
    {
        $owner = $this->createUserWithId(1);
        $owner->setEmail('owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($owner);

        $result = $this->voter->vote($token, $project, [ProjectVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectMemberCanViewProject(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $projectMember = $this->createUserWithId(2);
        $projectMember->setEmail('project_member@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);
        $project->addMember($projectMember);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectMember);

        $result = $this->voter->vote($token, $project, [ProjectVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectMemberCannotEditProject(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $projectMember = $this->createUserWithId(2);
        $projectMember->setEmail('project_member@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);
        $project->addMember($projectMember);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectMember);

        $result = $this->voter->vote($token, $project, [ProjectVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testProjectMemberCannotDeleteProject(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $projectMember = $this->createUserWithId(2);
        $projectMember->setEmail('project_member@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);
        $project->addMember($projectMember);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectMember);

        $result = $this->voter->vote($token, $project, [ProjectVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testOtherUserCannotAccessProject(): void
    {
        $otherUser = $this->createUserWithId(1);
        $otherUser->setEmail('other@example.com');

        $projectOwner = $this->createUserWithId(2);
        $projectOwner->setEmail('project_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($otherUser);

        $result = $this->voter->vote($token, $project, [ProjectVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }
}
