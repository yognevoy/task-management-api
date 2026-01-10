<?php

namespace App\Config\Infrastructure\Persistence\Doctrine\Type;

use App\Config\Domain\ValueObject\ConfigValue;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class ConfigValueType extends Type
{
    public function getName(): string
    {
        return 'config_value';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ConfigValue
    {
        if ($value === null) {
            return null;
        }

        return ConfigValue::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof ConfigValue) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $value->toString();
    }
}
