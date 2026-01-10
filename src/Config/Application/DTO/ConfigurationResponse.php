<?php

namespace App\Config\Application\DTO;

use App\Config\Domain\Entity\Configuration;

class ConfigurationResponse
{
    public string $key;
    public string $value;

    public static function fromEntity(Configuration $configuration): self
    {
        $dto = new self();

        $dto->key = $configuration->getKey()->value;
        $dto->value = $configuration->getValue()->toString();

        return $dto;
    }
}
