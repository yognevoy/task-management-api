<?php

namespace App\Comment\Application\Query\GetAllComments;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Entity\Comment;
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
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetAllCommentsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private TagAwareCacheInterface     $commentCache,
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

        if ($query->taskId || $query->authorId) {
            return $this->executeQuery($query, $currentUser, $pagination);
        }

        $cacheKey = $this->generateCacheKey($currentUser, $pagination);

        return $this->commentCache->get($cacheKey, function ($item) use ($query, $currentUser, $pagination) {
            $result = $this->executeQuery($query, $currentUser, $pagination);

            $this->addCacheTags($item, $currentUser);

            return $result;
        });
    }

    private function executeQuery(GetAllCommentsQuery $query, User $currentUser, $pagination): PaginatedResponse
    {
        $qb = $this->entityManager->createQueryBuilder();

        if ($query->taskId) {
            $task = $this->taskRepository->find($query->taskId);
            if (!$task) {
                throw new TaskNotFoundException();
            }

            $this->commentQueryBuilder->buildByTask($qb, $task);
            $total = $this->commentRepository->countByTask($task);
        } elseif ($query->authorId) {
            $user = $this->userRepository->find($query->authorId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            $this->commentQueryBuilder->buildByAuthor($qb, $user);
            $total = $this->commentRepository->countByAuthor($user);
        } else {
            if ($currentUser->isAdmin()) {
                $this->commentQueryBuilder->buildForAdmin($qb);
                $total = $this->commentRepository->countAll();
            } else {
                $this->commentQueryBuilder->buildForUser($qb, $currentUser);
                $total = $this->commentRepository->countByUser($currentUser);
            }
        }

        $comments = $qb
            ->orderBy('c.createdAt', 'ASC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->getQuery()
            ->getResult();

        $commentListResponse = new CommentListResponse($comments);
        return new PaginatedResponse($commentListResponse, $total, $pagination->getPage(), $pagination->getLimit());
    }

    private function generateCacheKey(User $currentUser, $pagination): string
    {
        if ($currentUser->isAdmin()) {
            return sprintf(
                'comments_all_page_%d_limit_%d',
                $pagination->getPage(),
                $pagination->getLimit()
            );
        } else {
            return sprintf(
                'comments_user_%d_page_%d_limit_%d',
                $currentUser->getId(),
                $pagination->getPage(),
                $pagination->getLimit()
            );
        }
    }

    private function addCacheTags(object $item, User $currentUser): void
    {
        if ($currentUser->isAdmin()) {
            $item->tag(['comments']);
        } else {
            $item->tag(['user_' . $currentUser->getId()]);
        }
    }
}
