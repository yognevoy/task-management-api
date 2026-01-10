<?php

namespace App\Config\Infrastructure\Repository;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configuration>
 */
class ConfigurationRepository extends ServiceEntityRepository implements ConfigurationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function findByKey(ConfigKey $key): ?Configuration
    {
        return $this->findOneBy(['key' => $key->value]);
    }
}
