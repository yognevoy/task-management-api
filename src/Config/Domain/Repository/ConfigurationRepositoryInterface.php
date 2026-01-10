<?php

namespace App\Config\Domain\Repository;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;

interface ConfigurationRepositoryInterface
{
    /**
     * @param ConfigKey $key
     * @return Configuration|null
     */
    public function findByKey(ConfigKey $key): ?Configuration;

    /**
     * @return Configuration[]
     */
    public function findAll();
}
