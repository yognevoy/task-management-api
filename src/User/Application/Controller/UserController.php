<?php

namespace App\User\Application\Controller;

use App\User\Infrastructure\Security\Voter\UserVoter;
use App\Shared\Domain\Exception\ValidationException;
use App\User\Application\DTO\UpdateUserRequest;
use App\User\Application\DTO\UserListResponse;
use App\User\Application\DTO\UserResponse;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        return $this->json(
            $this->userService->getAllUsers()
        );
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    #[IsGranted(UserVoter::VIEW, subject: 'user')]
    public function getOne(User $user): JsonResponse
    {
        return $this->json(
            $this->userService->getUserById($user->getId())
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted(UserVoter::EDIT, subject: 'user')]
    public function updateUser(int $id, #[MapRequestPayload] UpdateUserRequest $dto): JsonResponse
    {
        try {
            return $this->json(
                $this->userService->updateUser($id, $dto),
                Response::HTTP_OK
            );
        } catch (ValidationException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'details' => $e->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Update failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    #[IsGranted(UserVoter::DELETE, subject: 'user')]
    public function deleteUser(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Delete failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
