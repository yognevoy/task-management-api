<?php

namespace App\Config\Application\DTO;

use App\Config\Domain\Entity\Configuration;

class ConfigurationListResponse
{
    /** @var ConfigurationResponse[] */
    public array $configurations;

    public function __construct(array $configurations)
    {
        $this->configurations = array_map(
            fn(Configuration $config) => ConfigurationResponse::fromEntity($config),
            $configurations
        );
    }
}
