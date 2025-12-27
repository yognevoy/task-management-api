<?php

namespace App\Comment\Application\Service;

use App\Comment\Application\DTO\CommentListResponse;
use App\Comment\Application\DTO\CommentResponse;
use App\Comment\Application\DTO\CreateCommentRequest;
use App\Comment\Application\DTO\UpdateCommentRequest;
use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Shared\Domain\Exception\ValidationException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private ValidatorInterface         $validator,
        private CacheInterface             $commentCache,
    )
    {
    }

    /**
     * Creates a new comment.
     *
     * @param CreateCommentRequest $dto
     * @param User|null $currentUser
     * @return CommentResponse
     * @throws ValidationException
     */
    public function createComment(CreateCommentRequest $dto, ?User $currentUser = null): CommentResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task = $this->taskRepository->find($dto->taskId);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comment = new Comment();
        $comment->setContent($dto->content);
        $comment->setTask($task);
        $comment->setAuthor($currentUser);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->invalidateCache($comment);

        return CommentResponse::fromEntity($comment);
    }

    /**
     * Updates an existing comment.
     *
     * @param int $id
     * @param UpdateCommentRequest $dto
     * @param User|null $currentUser
     * @return CommentResponse
     * @throws ValidationException
     */
    public function updateComment(int $id, UpdateCommentRequest $dto, ?User $currentUser = null): CommentResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        if ($dto->content !== null) {
            $comment->setContent($dto->content);
        }

        $this->entityManager->flush();

        $this->invalidateCache($comment);

        return CommentResponse::fromEntity($comment);
    }

    /**
     * Deletes an existing comment.
     *
     * @param Comment $comment
     * @return void
     */
    public function deleteComment(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->invalidateCache($comment);
    }

    /**
     * Retrieves all comments.
     *
     * @param int|null $taskId
     * @param int|null $authorId
     * @param User|null $currentUser
     * @return CommentListResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllComments(?int $taskId = null, ?int $authorId = null, ?User $currentUser = null): CommentListResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $cacheKey = $taskId ? 'comments_task_' . $taskId :
            ($authorId ? 'comments_author_' . $authorId :
                ($currentUser->isAdmin() ? 'comments_all' : 'comments_user_' . $currentUser->getId()));

        return $this->commentCache->get($cacheKey, function () use ($taskId, $authorId, $currentUser) {
            if ($taskId !== null) {
                $task = $this->taskRepository->find($taskId);
                if (!$task) {
                    throw new TaskNotFoundException();
                }

                $comments = $this->commentRepository->findByTask($task);
            } elseif ($authorId !== null) {
                $user = $this->userRepository->find($authorId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                if ($currentUser->getId() !== $user->getId() && !$currentUser->isAdmin()) {
                    throw new AccessDeniedException();
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

    /**
     * Retrieves a comment by its ID.
     *
     * @param int $id
     * @return CommentResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCommentById(int $id): CommentResponse
    {
        $cacheKey = 'comment_' . $id;

        return $this->commentCache->get($cacheKey, function () use ($id) {
            $comment = $this->commentRepository->find($id);
            if (!$comment) {
                throw new CommentNotFoundException();
            }

            return CommentResponse::fromEntity($comment);
        });
    }

    /**
     * Retrieves comments for a given task.
     *
     * @param int $taskId
     * @return CommentListResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCommentsByTask(int $taskId): CommentListResponse
    {
        $cacheKey = 'comments_task_' . $taskId;

        return $this->commentCache->get($cacheKey, function () use ($taskId) {
            $task = $this->taskRepository->find($taskId);
            if (!$task) {
                throw new TaskNotFoundException();
            }

            $comments = $this->commentRepository->findByTask($task);

            return new CommentListResponse($comments);
        });
    }

    /**
     * Invalidates cache for a given comment.
     *
     * @param Comment $comment
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateCache(Comment $comment): void
    {
        $this->commentCache->delete('comment_' . $comment->getId());
        $this->commentCache->delete('comments_task_' . $comment->getTaskId());
        $this->commentCache->delete('comments_author_' . $comment->getAuthorId());
        $this->commentCache->delete('comments_all');
        $this->commentCache->delete('comments_user_' . $comment->getAuthorId());
    }
}
