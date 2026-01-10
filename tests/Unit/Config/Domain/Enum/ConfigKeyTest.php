<?php

namespace App\Tests\Unit\Config\Domain\Enum;

use App\Config\Domain\Enum\ConfigKey;
use PHPUnit\Framework\TestCase;

class ConfigKeyTest extends TestCase
{
    public function testConfigKeyHasCorrectValues(): void
    {
        $this->assertEquals('allow_user_registration', ConfigKey::ALLOW_USER_REGISTRATION->value);
        $this->assertEquals('max_members_per_project', ConfigKey::MAX_MEMBERS_PER_PROJECT->value);
        $this->assertEquals('max_assigned_tasks_per_user', ConfigKey::MAX_ASSIGNED_TASKS_PER_USER->value);
    }

    public function testConfigKeyCanConvertFromString(): void
    {
        $this->assertEquals(ConfigKey::ALLOW_USER_REGISTRATION, ConfigKey::from('allow_user_registration'));
        $this->assertEquals(ConfigKey::MAX_MEMBERS_PER_PROJECT, ConfigKey::from('max_members_per_project'));
        $this->assertEquals(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER, ConfigKey::from('max_assigned_tasks_per_user'));
    }

    public function testConfigKeyToValuesReturnsAllValues(): void
    {
        $expectedValues = ['allow_user_registration', 'max_members_per_project', 'max_assigned_tasks_per_user'];
        $actualValues = ConfigKey::toValues();

        $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
    }

    public function testConfigKeyFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ConfigKey::from('invalid_key');
    }

    public function testConfigKeyCasesReturnsAllCases(): void
    {
        $cases = ConfigKey::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(ConfigKey::ALLOW_USER_REGISTRATION, $cases);
        $this->assertContains(ConfigKey::MAX_MEMBERS_PER_PROJECT, $cases);
        $this->assertContains(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER, $cases);
    }
}
