<?php

namespace App\Tests\Unit\Config\Domain\Entity;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\ValueObject\ConfigValue;
use PHPUnit\Framework\TestCase;

class ConfigurationEntityTest extends TestCase
{
    public function testConfigurationCanBeCreatedWithValidParameters(): void
    {
        $key = ConfigKey::ALLOW_USER_REGISTRATION;
        $value = ConfigValue::fromBool(true);

        $configuration = new Configuration($key, $value);

        $this->assertNull($configuration->getId());
        $this->assertEquals($key, $configuration->getKey());
        $this->assertEquals($value, $configuration->getValue());
    }

    public function testSetKeyShouldSetKey(): void
    {
        $configuration = new Configuration(
            ConfigKey::ALLOW_USER_REGISTRATION,
            ConfigValue::fromBool(true)
        );

        $newKey = ConfigKey::MAX_MEMBERS_PER_PROJECT;

        $result = $configuration->setKey($newKey);

        $this->assertEquals($newKey, $configuration->getKey());
        $this->assertInstanceOf(Configuration::class, $result);
    }

    public function testSetValueShouldSetValue(): void
    {
        $configuration = new Configuration(
            ConfigKey::ALLOW_USER_REGISTRATION,
            ConfigValue::fromBool(true)
        );

        $newValue = ConfigValue::fromInt(10);

        $result = $configuration->setValue($newValue);

        $this->assertEquals($newValue, $configuration->getValue());
        $this->assertInstanceOf(Configuration::class, $result);
    }

    public function testGetValueShouldReturnCorrectValue(): void
    {
        $expectedValue = ConfigValue::fromString('test_value');
        $configuration = new Configuration(
            ConfigKey::ALLOW_USER_REGISTRATION,
            $expectedValue
        );

        $actualValue = $configuration->getValue();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testGetKeyShouldReturnCorrectKey(): void
    {
        $expectedKey = ConfigKey::MAX_ASSIGNED_TASKS_PER_USER;
        $configuration = new Configuration(
            $expectedKey,
            ConfigValue::fromBool(false)
        );

        $actualKey = $configuration->getKey();

        $this->assertEquals($expectedKey, $actualKey);
    }
}
