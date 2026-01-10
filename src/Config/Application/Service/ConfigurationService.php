<?php

namespace App\Config\Application\Service;

use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Config\Domain\ValueObject\ConfigValue;

class ConfigurationService
{
    private const DEFAULT_ALLOW_USER_REGISTRATION = true;
    private const DEFAULT_MAX_MEMBERS_PER_PROJECT = 100;
    private const DEFAULT_MAX_ASSIGNED_TASKS_PER_USER = 100;

    public function __construct(
        private ConfigurationRepositoryInterface $configurationRepository
    )
    {
    }

    public function isUserRegistrationAllowed(): bool
    {
        $config = $this->configurationRepository->findByKey(ConfigKey::ALLOW_USER_REGISTRATION);

        if (!$config) {
            return self::DEFAULT_ALLOW_USER_REGISTRATION;
        }

        return $config->getValue()->toBool();
    }

    public function getMaxMembersPerProject(): int
    {
        $config = $this->configurationRepository->findByKey(ConfigKey::MAX_MEMBERS_PER_PROJECT);

        if (!$config) {
            return self::DEFAULT_MAX_MEMBERS_PER_PROJECT;
        }

        return $config->getValue()->toInt();
    }

    public function getMaxAssignedTasksPerUser(): int
    {
        $config = $this->configurationRepository->findByKey(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER);

        if (!$config) {
            return self::DEFAULT_MAX_ASSIGNED_TASKS_PER_USER;
        }

        return $config->getValue()->toInt();
    }

    public function getValue(ConfigKey $key): ?ConfigValue
    {
        $config = $this->configurationRepository->findByKey($key);

        return $config?->getValue();
    }
}
