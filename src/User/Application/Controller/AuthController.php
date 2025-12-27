<?php

namespace App\User\Application\Controller;

use App\User\Application\DTO\CreateUserRequest;
use App\User\Application\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserService $userService,
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
        try {
            $userId = $this->userService->registerUser($dto);

            return $this->json([
                'message' => 'User registered successfully',
                'user_id' => $userId
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Registration failed: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
