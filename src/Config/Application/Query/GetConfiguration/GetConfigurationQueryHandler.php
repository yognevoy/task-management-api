<?php

namespace App\Config\Application\Query\GetConfiguration;

use App\Config\Application\DTO\ConfigurationListResponse;
use App\Config\Domain\Repository\ConfigurationRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetConfigurationQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ConfigurationRepositoryInterface $configurationRepository,
        private CacheInterface                   $configCache
    )
    {
    }

    public function __invoke(GetConfigurationQuery $query): ConfigurationListResponse
    {
        $cacheKey = 'configuration';

        return $this->configCache->get($cacheKey, function () {
            $configurations = $this->configurationRepository->findAll();

            return new ConfigurationListResponse($configurations);
        });
    }
}
