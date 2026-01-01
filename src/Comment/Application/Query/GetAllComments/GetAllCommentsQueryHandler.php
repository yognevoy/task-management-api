<?php

namespace App\Comment\Application\Query\GetAllComments;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
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

        if ($query->taskId) {
            $cacheKey = sprintf(
                'comments_task_%d_page_%d_limit_%d',
                $query->taskId,
                $pagination->getPage(),
                $pagination->getLimit()
            );
        } elseif ($query->authorId) {
            $cacheKey = sprintf(
                'comments_author_%d_page_%d_limit_%d',
                $query->authorId,
                $pagination->getPage(),
                $pagination->getLimit()
            );
        } elseif ($currentUser->isAdmin()) {
            $cacheKey = sprintf(
                'comments_all_page_%d_limit_%d',
                $pagination->getPage(),
                $pagination->getLimit()
            );
        } else {
            $cacheKey = sprintf(
                'comments_user_%d_page_%d_limit_%d',
                $currentUser->getId(),
                $pagination->getPage(),
                $pagination->getLimit()
            );
        }

        return $this->commentCache->get($cacheKey, function ($item) use ($query, $currentUser, $pagination) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('c')
                ->from(Comment::class, 'c');

            if ($query->taskId) {
                $task = $this->taskRepository->find($query->taskId);
                if (!$task) {
                    throw new TaskNotFoundException();
                }

                $qb->where('c.task = :task')
                    ->setParameter('task', $task);

                $total = $this->commentRepository->countByTask($task);
            } elseif ($query->authorId) {
                $user = $this->userRepository->find($query->authorId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                $qb->where('c.author = :author')
                    ->setParameter('author', $user);

                $total = $this->commentRepository->countByAuthor($user);
            } else {
                if (!$currentUser->isAdmin()) {
                    $qb->join('c.task', 't')
                        ->leftJoin('t.project', 'p')
                        ->leftJoin('p.members', 'm')
                        ->where('c.author = :user OR t.owner = :user OR t.assignee = :user OR p.owner = :user OR m = :user')
                        ->setParameter('user', $currentUser);

                    $total = $this->commentRepository->countByUser($currentUser);
                } else {
                    $total = $this->commentRepository->countAll();
                }
            }

            $comments = $qb
                ->orderBy('c.createdAt', 'ASC')
                ->setFirstResult($pagination->getOffset())
                ->setMaxResults($pagination->getLimit())
                ->getQuery()
                ->getResult();

            if ($query->taskId) {
                $item->tag(['task_' . $query->taskId]);
            } elseif ($query->authorId) {
                $item->tag(['author_' . $query->authorId]);
            } else {
                $item->tag(['comments']);
            }

            $commentListResponse = new CommentListResponse($comments);
            return new PaginatedResponse($commentListResponse, $total, $pagination->getPage(), $pagination->getLimit());
        });
    }
}
