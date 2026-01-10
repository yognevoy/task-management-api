<?php

namespace App\Config\Application\Controller;

use App\Config\Application\Command\SetConfiguration\SetConfigurationCommand;
use App\Config\Application\DTO\SetConfigurationRequest;
use App\Config\Application\Query\GetConfiguration\GetConfigurationQuery;
use App\Config\Infrastructure\Security\Voter\ConfigurationVoter;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/config', name: 'api_config_')]
class ConfigurationController extends AbstractController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface   $queryBus,
    )
    {
    }

    /**
     * Sets configuration values.
     */
    #[Route('', name: 'set', methods: ['POST'])]
    public function setConfiguration(#[MapRequestPayload] SetConfigurationRequest $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted(ConfigurationVoter::EDIT, null);

        $command = new SetConfigurationCommand(
            $dto->configurations,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result);
    }

    /**
     * Retrieves all configuration values.
     */
    #[Route('', name: 'get', methods: ['GET'])]
    public function getConfiguration(): JsonResponse
    {
        $this->denyAccessUnlessGranted(ConfigurationVoter::VIEW, null);

        $query = new GetConfigurationQuery();
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }
}
