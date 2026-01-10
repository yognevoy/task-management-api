<?php

namespace App\Config\Application\Command\SetConfiguration;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\Exception\InvalidConfigurationException;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Config\Domain\ValueObject\ConfigValue;
use App\Config\Infrastructure\Cache\ConfigCacheManager;
use App\Shared\Application\Command\CommandHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

class SetConfigurationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $configurationRepository,
        private EntityManagerInterface           $entityManager,
        private ConfigCacheManager               $configCacheManager
    )
    {
    }

    public function __invoke(SetConfigurationCommand $command): array
    {
        $results = [];

        foreach ($command->configurations as $key => $value) {
            $configKey = ConfigKey::tryFrom($key);
            if ($configKey === null) {
                throw new InvalidConfigurationException($key);
            }

            $configValue = ConfigValue::fromString($value);

            $existingConfig = $this->configurationRepository->findByKey($configKey);

            if ($existingConfig) {
                $existingConfig->setValue($configValue);
                $this->entityManager->persist($existingConfig);
            } else {
                $configuration = new Configuration($configKey, $configValue);
                $this->entityManager->persist($configuration);
            }

            $config = $existingConfig ?? $configuration;

            $results[] = [
                'key' => $config->getKey()->value,
                'value' => $config->getValue()->toString(),
            ];
        }

        $this->entityManager->flush();

        $this->configCacheManager->invalidateCache();

        return $results;
    }
}
