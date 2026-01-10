<?php

namespace App\Tests\Unit\Config\Domain\ValueObject;

use App\Config\Domain\ValueObject\ConfigValue;
use PHPUnit\Framework\TestCase;

class ConfigValueTest extends TestCase
{
    public function testFromStringShouldCreateConfigValue(): void
    {
        $value = 'test_value';
        $configValue = ConfigValue::fromString($value);

        $this->assertEquals($value, $configValue->getValue());
        $this->assertEquals($value, $configValue->toString());
    }

    public function testFromIntShouldCreateConfigValue(): void
    {
        $intValue = 42;
        $configValue = ConfigValue::fromInt($intValue);

        $this->assertEquals((string)$intValue, $configValue->getValue());
        $this->assertEquals($intValue, $configValue->toInt());
    }

    public function testFromBoolShouldCreateConfigValue(): void
    {
        $boolValue = true;
        $configValue = ConfigValue::fromBool($boolValue);

        $this->assertEquals('true', $configValue->getValue());
        $this->assertTrue($configValue->toBool());
    }

    public function testFromBoolFalseShouldCreateConfigValue(): void
    {
        $boolValue = false;
        $configValue = ConfigValue::fromBool($boolValue);

        $this->assertEquals('false', $configValue->getValue());
        $this->assertFalse($configValue->toBool());
    }

    public function testToStringShouldReturnStringValue(): void
    {
        $value = 'hello_world';
        $configValue = ConfigValue::fromString($value);

        $this->assertEquals($value, $configValue->toString());
    }

    public function testToIntShouldReturnIntValue(): void
    {
        $value = '123';
        $configValue = ConfigValue::fromString($value);

        $this->assertEquals(123, $configValue->toInt());
    }

    public function testToBoolShouldReturnTrueForTrueString(): void
    {
        $configValue = ConfigValue::fromString('true');

        $this->assertTrue($configValue->toBool());
    }

    public function testToBoolShouldReturnTrueForOneString(): void
    {
        $configValue = ConfigValue::fromString('1');

        $this->assertTrue($configValue->toBool());
    }

    public function testToBoolShouldReturnFalseForFalseString(): void
    {
        $configValue = ConfigValue::fromString('false');

        $this->assertFalse($configValue->toBool());
    }

    public function testToBoolShouldReturnFalseForZeroString(): void
    {
        $configValue = ConfigValue::fromString('0');

        $this->assertFalse($configValue->toBool());
    }

    public function testToBoolShouldReturnFalseForOtherStrings(): void
    {
        $configValue = ConfigValue::fromString('other');

        $this->assertFalse($configValue->toBool());
    }

    public function testGetValueShouldReturnStoredValue(): void
    {
        $value = 'stored_value';
        $configValue = ConfigValue::fromString($value);

        $this->assertEquals($value, $configValue->getValue());
    }

    public function testToStringMagicMethodShouldReturnValue(): void
    {
        $value = 'magic_value';
        $configValue = ConfigValue::fromString($value);

        $this->assertEquals($value, (string)$configValue);
    }
}
