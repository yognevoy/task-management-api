<?php

namespace App\User\Application\Controller;

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Domain\ValueObject\Pagination;
use App\User\Application\Command\DeleteUser\DeleteUserCommand;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Application\DTO\UpdateUserRequest;
use App\User\Application\Query\GetAllUsers\GetAllUsersQuery;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Security\Voter\UserVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private CommandBusInterface     $commandBus,
        private QueryBusInterface       $queryBus,
        private UserRepositoryInterface $userRepository,
    )
    {
    }

    /**
     * Updates an existing user.
     *
     * @param int $id
     * @param UpdateUserRequest $dto
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateUser(int $id, #[MapRequestPayload] UpdateUserRequest $dto): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $command = new UpdateUserCommand(
            $id,
            $dto->email,
            $dto->password,
            $dto->roles
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * Deletes an existing user.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $command = new DeleteUserCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieves all users.
     *
     * @return JsonResponse
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllUsers(Request $request): JsonResponse
    {
        $page = $request->query->get('page');
        $limit = $request->query->get('limit');

        $pagination = Pagination::create($page, $limit);
        $query = new GetAllUsersQuery($pagination);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves a user by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        $query = new GetUserQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }
}
