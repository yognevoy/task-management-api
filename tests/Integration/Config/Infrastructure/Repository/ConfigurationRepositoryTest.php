<?php

namespace App\Tests\Integration\Config\Infrastructure\Repository;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Config\Domain\ValueObject\ConfigValue;
use App\Config\Infrastructure\DataFixtures\ConfigurationFixtures;
use App\Tests\Integration\BaseTestCase;

class ConfigurationRepositoryTest extends BaseTestCase
{
    private ConfigurationRepositoryInterface $configurationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([ConfigurationFixtures::class]);
        /** @var ConfigurationRepositoryInterface $configurationRepository */
        $configurationRepository = $this->getEntityManager()->getRepository(Configuration::class);
        $this->configurationRepository = $configurationRepository;
    }

    public function testFindByKeyReturnsCorrectConfiguration(): void
    {
        $config = $this->configurationRepository->findByKey(ConfigKey::MAX_MEMBERS_PER_PROJECT);

        $this->assertNotNull($config);
        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals(ConfigKey::MAX_MEMBERS_PER_PROJECT, $config->getKey());
        $this->assertEquals(100, $config->getValue()->toInt());
    }

    public function testUpdateExistingConfiguration(): void
    {
        $existingConfig = $this->configurationRepository->findByKey(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER);

        $this->assertNotNull($existingConfig);
        $this->assertEquals(100, $existingConfig->getValue()->toInt());

        $existingConfig->setValue(ConfigValue::fromInt(200));

        $this->getEntityManager()->flush();

        $updatedConfig = $this->configurationRepository->findByKey(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER);

        $this->assertNotNull($updatedConfig);
        $this->assertEquals(ConfigKey::MAX_ASSIGNED_TASKS_PER_USER, $updatedConfig->getKey());
        $this->assertEquals(200, $updatedConfig->getValue()->toInt());
    }
}
