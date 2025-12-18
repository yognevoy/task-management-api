<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\AccessDeniedException;
use App\Exception\CommentNotFoundException;
use App\Exception\TaskNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/comments', name: 'api_comments_')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllComments(Request $request): JsonResponse
    {
        $taskId = $request->query->getInt('task');
        $authorId = $request->query->getInt('author');

        if ($taskId) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskId);
            if (!$task) {
                throw new TaskNotFoundException();
            }

            $comments = $this->commentRepository->findByTask($task);

            return $this->json($comments, context: ['groups' => 'comment:read']);
        }

        if ($authorId) {
            $user = $this->entityManager->getRepository(User::class)->find($authorId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            $comments = $this->commentRepository->findByAuthor($user);

            return $this->json($comments, context: ['groups' => 'comment:read']);
        }

        $comments = $this->commentRepository->findAll();

        return $this->json($comments, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            throw new CommentNotFoundException();
        }

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['taskId'])) {
            return $this->json(['error' => 'Task ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($data['taskId']);
        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setTask($task);

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $comment->setAuthor($currentUser);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json($comment, Response::HTTP_CREATED, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateComment(int $id, Request $request): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $comment->setContent($data['content']);
        }

        $this->entityManager->flush();

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/task/{taskId}', name: 'get_by_task', methods: ['GET'])]
    public function getCommentsByTask(int $taskId): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $comments = $this->commentRepository->findByTask($task);

        return $this->json($comments, context: ['groups' => 'comment:read']);
    }
}
