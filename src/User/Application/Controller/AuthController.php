<?php

namespace App\User\Application\Controller;

use App\Shared\Application\Command\CommandBusInterface;
use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Application\DTO\CreateUserRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    )
    {
    }

    /**
     * Registers a new user.
     *
     * @param CreateUserRequest $dto
     * @return JsonResponse
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] CreateUserRequest $dto): JsonResponse
    {
        $command = new RegisterUserCommand(
            $dto->email,
            $dto->password,
            $dto->roles ?? []
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json([
            'message' => 'User registered successfully',
            'user' => $result
        ], Response::HTTP_CREATED);
    }
}
