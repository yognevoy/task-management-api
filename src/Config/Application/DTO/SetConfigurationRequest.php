<?php

namespace App\Config\Application\DTO;

class SetConfigurationRequest
{
    public function __construct(
        public readonly array $configurations
    )
    {
    }
}
