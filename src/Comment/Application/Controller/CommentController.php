<?php

namespace App\Comment\Application\Controller;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Security\Voter\CommentVoter;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/comments', name: 'api_comments_')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllComments(Request $request): JsonResponse
    {
        $taskId = $request->query->getInt('task');
        $authorId = $request->query->getInt('author');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        if ($taskId) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskId);
            if (!$task) {
                throw new TaskNotFoundException();
            }

            $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

            $comments = $this->commentRepository->findByTask($task);

            return $this->json($comments, context: ['groups' => 'comment:read']);
        }

        if ($authorId) {
            $user = $this->entityManager->getRepository(User::class)->find($authorId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            if ($currentUser->getId() !== $user->getId() && !$currentUser->isAdmin()) {
                throw new AccessDeniedException();
            }

            $comments = $this->commentRepository->findByAuthor($user);

            return $this->json($comments, context: ['groups' => 'comment:read']);
        }

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

            return $this->json($comments, context: ['groups' => 'comment:read']);
        }

        $comments = $this->commentRepository->findAll();

        return $this->json($comments, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    #[IsGranted(CommentVoter::VIEW, subject: 'comment')]
    public function getComment(Comment $comment): JsonResponse
    {
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

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

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
    #[IsGranted(CommentVoter::EDIT, subject: 'comment')]
    public function updateComment(Comment $comment, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $comment->setContent($data['content']);
        }

        $this->entityManager->flush();

        return $this->json($comment, context: ['groups' => 'comment:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted(CommentVoter::DELETE, subject: 'comment')]
    public function deleteComment(Comment $comment): JsonResponse
    {
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

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $comments = $this->commentRepository->findByTask($task);

        return $this->json($comments, context: ['groups' => 'comment:read']);
    }
}
