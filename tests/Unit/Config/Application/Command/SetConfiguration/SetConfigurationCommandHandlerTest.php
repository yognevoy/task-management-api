<?php

namespace App\Tests\Unit\Config\Application\Command\SetConfiguration;

use App\Config\Application\Command\SetConfiguration\SetConfigurationCommand;
use App\Config\Application\Command\SetConfiguration\SetConfigurationCommandHandler;
use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Config\Infrastructure\Cache\ConfigCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SetConfigurationCommandHandlerTest extends TestCase
{
    private SetConfigurationCommandHandler $handler;
    private ConfigurationRepositoryInterface|MockObject $configurationRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private ConfigCacheManager|MockObject $configCacheManager;

    protected function setUp(): void
    {
        $this->configurationRepository = $this->createMock(ConfigurationRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->configCacheManager = $this->createMock(ConfigCacheManager::class);

        $this->handler = new SetConfigurationCommandHandler(
            $this->configurationRepository,
            $this->entityManager,
            $this->configCacheManager
        );
    }

    public function testHandlerShouldSetConfigurationSuccessfully(): void
    {
        $configurations = [
            'allow_user_registration' => 'true',
            'max_members_per_project' => '10'
        ];

        $command = new SetConfigurationCommand($configurations);

        $this->configurationRepository
            ->expects($this->exactly(2))
            ->method('findByKey')
            ->willReturn(null);

        $persistedConfigs = [];
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($config) use (&$persistedConfigs) {
                $persistedConfigs[] = $config;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->configCacheManager
            ->expects($this->once())
            ->method('invalidateCache');

        $result = ($this->handler)($command);

        $this->assertCount(2, $result);
        $this->assertEquals([
            ['key' => 'allow_user_registration', 'value' => 'true'],
            ['key' => 'max_members_per_project', 'value' => '10']
        ], $result);

        $this->assertCount(2, $persistedConfigs);
        foreach ($persistedConfigs as $config) {
            $this->assertInstanceOf(Configuration::class, $config);
        }
    }
}
