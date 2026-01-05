<?php

namespace App\Tests\Unit\User\Infrastructure\Security\Voter;

use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoterTest extends TestCase
{
    use EntityFactoryTrait;

    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    public function testAdminUserCanViewUser(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanEditUser(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanDeleteUser(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCanViewOwnProfile(): void
    {
        $user = $this->createUserWithId(1);
        $user->setEmail('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($token, $user, [UserVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCanEditOwnProfile(): void
    {
        $user = $this->createUserWithId(1);
        $user->setEmail('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($token, $user, [UserVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCanDeleteOwnProfile(): void
    {
        $user = $this->createUserWithId(1);
        $user->setEmail('user@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        $result = $this->voter->vote($token, $user, [UserVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEditOtherUser(): void
    {
        $currentUser = $this->createUserWithId(1);
        $currentUser->setEmail('current@example.com');

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testUserCannotDeleteOtherUser(): void
    {
        $currentUser = $this->createUserWithId(1);
        $currentUser->setEmail('current@example.com');

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testUserCanViewOtherUser(): void
    {
        $currentUser = $this->createUserWithId(1);
        $currentUser->setEmail('current@example.com');

        $targetUser = $this->createUserWithId(2);
        $targetUser->setEmail('target@example.com');

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, [UserVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }
}
