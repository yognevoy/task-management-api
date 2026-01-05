<?php

namespace App\Tests\Unit\User\Domain\Entity;

use App\Comment\Domain\Entity\Comment;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    use EntityFactoryTrait;

    public function testUserCanBeCreatedWithDefaultValues(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getEmail());
        $this->assertEmpty($user->getTasks());
        $this->assertEmpty($user->getProjects());
        $this->assertEmpty($user->getComments());
        $this->assertNotEmpty($user->getRoles());
    }

    public function testSetEmailShouldSetEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';

        $result = $user->setEmail($email);

        $this->assertEquals($email, $user->getEmail());
        $this->assertSame($user, $result);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);

        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testGetRolesReturnsRolesWithDefaultUserRole(): void
    {
        $user = new User();

        $roles = $user->getRoles();

        $this->assertContains(UserRole::USER->value, $roles);
        $this->assertCount(1, $roles);
    }

    public function testSetRolesShouldSetRoles(): void
    {
        $user = new User();
        $roles = [UserRole::ADMIN, 'ROLE_MANAGER'];

        $result = $user->setRoles($roles);

        $expectedRoles = [UserRole::ADMIN->value, 'ROLE_MANAGER', UserRole::USER->value];
        $this->assertEqualsCanonicalizing($expectedRoles, $user->getRoles());
        $this->assertSame($user, $result);
    }

    public function testAddRoleShouldAddRole(): void
    {
        $user = new User();
        $role = UserRole::ADMIN;

        $result = $user->addRole($role);

        $this->assertContains($role->value, $user->getRoles());
        $this->assertSame($user, $result);
    }

    public function testAddRoleWithStringValueShouldAddRole(): void
    {
        $user = new User();
        $role = 'ROLE_MANAGER';

        $user->addRole($role);

        $this->assertContains($role, $user->getRoles());
    }

    public function testRemoveRoleShouldRemoveRole(): void
    {
        $user = new User();
        $role = UserRole::ADMIN;
        $user->addRole($role);

        $result = $user->removeRole($role);

        $this->assertNotContains($role->value, $user->getRoles());
        $this->assertSame($user, $result);
    }

    public function testRemoveRoleWithStringValueShouldRemoveRole(): void
    {
        $user = new User();
        $role = 'ROLE_MANAGER';
        $user->addRole($role);

        $user->removeRole($role);

        $this->assertNotContains($role, $user->getRoles());
    }

    public function testIsAdminReturnsTrueWhenUserHasAdminRole(): void
    {
        $user = new User();
        $user->addRole(UserRole::ADMIN);

        $this->assertTrue($user->isAdmin());
    }

    public function testIsAdminReturnsFalseWhenUserDoesNotHaveAdminRole(): void
    {
        $user = new User();

        $this->assertFalse($user->isAdmin());
    }

    public function testSetPasswordShouldSetPassword(): void
    {
        $user = new User();
        $password = 'securePassword';

        $result = $user->setPassword($password);

        $this->assertEquals($password, $user->getPassword());
        $this->assertSame($user, $result);
    }

    public function testAddCommentShouldAddComment(): void
    {
        $user = new User();
        $comment = new Comment();

        $result = $user->addComment($comment);

        $this->assertCount(1, $user->getComments());
        $this->assertTrue($user->getComments()->contains($comment));
        $this->assertSame($user, $result);
    }

    public function testRemoveCommentShouldRemoveComment(): void
    {
        $user = new User();
        $comment = new Comment();
        $user->addComment($comment);

        $result = $user->removeComment($comment);

        $this->assertCount(0, $user->getComments());
        $this->assertFalse($user->getComments()->contains($comment));
        $this->assertSame($user, $result);
    }
}
