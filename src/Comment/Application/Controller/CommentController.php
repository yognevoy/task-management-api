<?php

namespace App\Comment\Application\Controller;

use App\Comment\Application\DTO\CreateCommentRequest;
use App\Comment\Application\DTO\UpdateCommentRequest;
use App\Comment\Application\Service\CommentService;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Security\Voter\CommentVoter;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
use App\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/comments', name: 'api_comments_')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentService             $commentService,
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
    )
    {
    }

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllComments(Request $request): JsonResponse
    {
        $taskId = $request->query->getInt('task');
        $authorId = $request->query->getInt('author');

        return $this->json(
            $this->commentService->getAllComments(
                $taskId, $authorId, $this->getUser()
            )
        );
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::VIEW, $comment);

        return $this->json(
            $this->commentService->getCommentById($id)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createComment(#[MapRequestPayload] CreateCommentRequest $dto): JsonResponse
    {
        return $this->json(
            $this->commentService->createComment(
                $dto, $this->getUser()
            ),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateComment(int $id, #[MapRequestPayload] UpdateCommentRequest $dto): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::EDIT, $comment);

        return $this->json(
            $this->commentService->updateComment(
                $id, $dto, $this->getUser()
            )
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::DELETE, $comment);

        $this->commentService->deleteComment($comment);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/task/{taskId}', name: 'get_by_task', methods: ['GET'])]
    public function getCommentsByTask(int $taskId): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        return $this->json(
            $this->commentService->getCommentsByTask($taskId)
        );
    }
}
