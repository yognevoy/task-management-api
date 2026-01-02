<?php

namespace App\Comment\Application\Query\GetAllComments;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Query\CommentQueryBuilder;
use App\Shared\Application\DTO\PaginatedResponse;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class GetAllCommentsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private CommentQueryBuilder        $commentQueryBuilder,
    )
    {
    }

    public function __invoke(GetAllCommentsQuery $query): PaginatedResponse
    {
        $currentUser = $query->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $pagination = $query->pagination;

        return $this->executeQuery($query, $currentUser, $pagination);
    }

    private function executeQuery(GetAllCommentsQuery $query, User $currentUser, $pagination): PaginatedResponse
    {
        $qb = $this->entityManager->createQueryBuilder();
        $total = $this->applyFilters($qb, $query, $currentUser);

        $comments = $qb
            ->orderBy('c.createdAt', 'ASC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->getQuery()
            ->getResult();

        $commentListResponse = new CommentListResponse($comments);

        return new PaginatedResponse(
            $commentListResponse,
            $total,
            $pagination->getPage(),
            $pagination->getLimit()
        );
    }

    private function applyFilters($qb, GetAllCommentsQuery $query, User $currentUser): int
    {
        if ($query->taskId) {
            return $this->applyTaskFilter($qb, $query->taskId);
        }

        if ($query->authorId) {
            return $this->applyAuthorFilter($qb, $query->authorId);
        }

        if ($currentUser->isAdmin()) {
            return $this->applyAdminFilter($qb);
        }

        return $this->applyUserFilter($qb, $currentUser);
    }

    private function applyTaskFilter($qb, int $taskId): int
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->commentQueryBuilder->buildByTask($qb, $task);
        return $this->commentRepository->countByTask($task);
    }

    private function applyAuthorFilter($qb, int $authorId): int
    {
        $user = $this->userRepository->find($authorId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->commentQueryBuilder->buildByAuthor($qb, $user);
        return $this->commentRepository->countByAuthor($user);
    }

    private function applyAdminFilter($qb): int
    {
        $this->commentQueryBuilder->buildForAdmin($qb);
        return $this->commentRepository->countAll();
    }

    private function applyUserFilter($qb, User $currentUser): int
    {
        $this->commentQueryBuilder->buildForUser($qb, $currentUser);
        return $this->commentRepository->countByUser($currentUser);
    }
}
