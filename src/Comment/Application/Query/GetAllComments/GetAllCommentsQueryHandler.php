<?php

namespace App\Comment\Application\Query\GetAllComments;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetAllCommentsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private CacheInterface             $commentCache,
    )
    {
    }

    public function __invoke(GetAllCommentsQuery $query): CommentListResponse
    {
        $currentUser = $query->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $cacheKey = $query->taskId ? 'comments_task_' . $query->taskId :
            ($query->authorId ? 'comments_author_' . $query->authorId :
                ($currentUser->isAdmin() ? 'comments_all' : 'comments_user_' . $currentUser->getId()));

        return $this->commentCache->get($cacheKey, function () use ($query, $currentUser) {
            if ($query->taskId !== null) {
                $task = $this->taskRepository->find($query->taskId);
                if (!$task) {
                    throw new TaskNotFoundException();
                }

                $comments = $this->commentRepository->findByTask($task);
            } elseif ($query->authorId !== null) {
                $user = $this->userRepository->find($query->authorId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                $comments = $this->commentRepository->findByAuthor($user);
            } else {
                if (!$currentUser->isAdmin()) {
                    $qb = $this->entityManager->createQueryBuilder();
                    $comments = $qb
                        ->select('c')
                        ->from(Comment::class, 'c')
                        ->join('c.task', 't')
                        ->leftJoin('t.project', 'p')
                        ->where('t.owner = :user OR p.owner = :user')
                        ->setParameter('user', $currentUser)
                        ->orderBy('c.createdAt', 'ASC')
                        ->getQuery()
                        ->getResult();
                } else {
                    $comments = $this->commentRepository->findAll();
                }
            }

            return new CommentListResponse($comments);
        });
    }
}
