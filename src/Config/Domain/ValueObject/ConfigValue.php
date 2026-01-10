<?php

namespace App\Config\Domain\ValueObject;

class ConfigValue
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromInt(int $value): self
    {
        return new self((string)$value);
    }

    public static function fromBool(bool $value): self
    {
        return new self($value ? 'true' : 'false');
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toInt(): int
    {
        return (int)$this->value;
    }

    public function toBool(): bool
    {
        return $this->value === 'true' || $this->value === '1';
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
