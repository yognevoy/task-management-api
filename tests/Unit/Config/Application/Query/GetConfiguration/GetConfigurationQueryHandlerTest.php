<?php

namespace App\Tests\Unit\Config\Application\Query\GetConfiguration;

use App\Config\Application\DTO\ConfigurationListResponse;
use App\Config\Application\Query\GetConfiguration\GetConfigurationQuery;
use App\Config\Application\Query\GetConfiguration\GetConfigurationQueryHandler;
use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Config\Domain\ValueObject\ConfigValue;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
class GetConfigurationQueryHandlerTest extends TestCase
{
    private GetConfigurationQueryHandler $handler;
    private ConfigurationRepositoryInterface|MockObject $configurationRepository;
    private CacheInterface|MockObject $configCache;

    protected function setUp(): void
    {
        $this->configurationRepository = $this->createMock(ConfigurationRepositoryInterface::class);
        $this->configCache = $this->createMock(CacheInterface::class);

        $this->handler = new GetConfigurationQueryHandler(
            $this->configurationRepository,
            $this->configCache
        );
    }

    public function testHandlerShouldReturnConfigurationSuccessfully(): void
    {
        $configurations = [
            new Configuration(ConfigKey::ALLOW_USER_REGISTRATION, ConfigValue::fromBool(true)),
            new Configuration(ConfigKey::MAX_MEMBERS_PER_PROJECT, ConfigValue::fromInt(5)),
        ];

        $this->configurationRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($configurations);

        $this->configCache
            ->expects($this->once())
            ->method('get')
            ->with('configuration')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $query = new GetConfigurationQuery();
        $result = ($this->handler)($query);

        $this->assertInstanceOf(ConfigurationListResponse::class, $result);
        $this->assertIsArray($result->configurations);
        $this->assertCount(2, $result->configurations);
    }
}
