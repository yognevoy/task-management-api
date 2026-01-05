<?php

namespace App\Tests\Unit\User\Domain\Enum;

use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function testUserRoleHasCorrectValues(): void
    {
        $this->assertEquals('ROLE_USER', UserRole::USER->value);
        $this->assertEquals('ROLE_ADMIN', UserRole::ADMIN->value);
    }

    public function testUserRoleCanConvertFromString(): void
    {
        $this->assertEquals(UserRole::USER, UserRole::from('ROLE_USER'));
        $this->assertEquals(UserRole::ADMIN, UserRole::from('ROLE_ADMIN'));
    }

    public function testUserRoleToValuesReturnsAllValues(): void
    {
        $expectedValues = ['ROLE_USER', 'ROLE_ADMIN'];
        $actualValues = UserRole::toValues();

        $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
    }

    public function testUserRoleFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        UserRole::from('invalid_role');
    }

    public function testUserRoleCasesReturnsAllCases(): void
    {
        $cases = UserRole::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(UserRole::USER, $cases);
        $this->assertContains(UserRole::ADMIN, $cases);
    }
}
