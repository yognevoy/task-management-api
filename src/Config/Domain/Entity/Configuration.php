<?php

namespace App\Config\Domain\Entity;

use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\ValueObject\ConfigValue;

class Configuration
{
    private ?int $id = null;
    private ConfigKey $key;
    private ConfigValue $value;

    public function __construct(ConfigKey $key, ConfigValue $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ConfigKey
    {
        return $this->key;
    }

    public function setKey(ConfigKey $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): ConfigValue
    {
        return $this->value;
    }

    public function setValue(ConfigValue $value): static
    {
        $this->value = $value;

        return $this;
    }
}
