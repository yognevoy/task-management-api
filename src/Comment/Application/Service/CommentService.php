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
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

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

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new ValidationException($messages);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return CommentResponse::fromEntity($comment);
    }

    public function updateComment(int $id, UpdateCommentRequest $dto, ?User $currentUser = null): CommentResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        if ($dto->content !== null) {
            $comment->setContent($dto->content);
        }

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new ValidationException($messages);
        }

        $this->entityManager->flush();

        return CommentResponse::fromEntity($comment);
    }

    public function deleteComment(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }

    public function getAllComments(?int $taskId = null, ?int $authorId = null, ?User $currentUser = null): CommentListResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

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
    }

    public function getCommentById(int $id): CommentResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        return CommentResponse::fromEntity($comment);
    }

    public function getCommentsByTask(int $taskId): CommentListResponse
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comments = $this->commentRepository->findByTask($task);

        return new CommentListResponse($comments);
    }
}
