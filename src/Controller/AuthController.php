<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserService $userService,
    )
    {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userService->registerUser($email, $password);

            return $this->json([
                'message' => 'User registered successfully',
                'user_id' => $user->getId()
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => 'Validation failed',
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Registration failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login_check', name: 'api_login_check', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json([]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
