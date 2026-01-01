<?php

namespace App\Comment\Application\Controller;

use App\Comment\Application\Command\CreateComment\CreateCommentCommand;
use App\Comment\Application\Command\DeleteComment\DeleteCommentCommand;
use App\Comment\Application\Command\UpdateComment\UpdateCommentCommand;
use App\Comment\Application\DTO\CreateCommentRequest;
use App\Comment\Application\DTO\UpdateCommentRequest;
use App\Comment\Application\Query\GetAllComments\GetAllCommentsQuery;
use App\Comment\Application\Query\GetComment\GetCommentQuery;
use App\Comment\Application\Query\GetCommentsByTask\GetCommentsByTaskQuery;
use App\Comment\Domain\Exception\CommentNotFoundException;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Comment\Infrastructure\Security\Voter\CommentVoter;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Domain\ValueObject\Pagination;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Security\Voter\TaskVoter;
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
        private CommandBusInterface        $commandBus,
        private QueryBusInterface          $queryBus,
        private CommentRepositoryInterface $commentRepository,
        private TaskRepositoryInterface    $taskRepository,
    )
    {
    }

    /**
     * Creates a new comment.
     *
     * @param CreateCommentRequest $dto
     * @return JsonResponse
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createComment(#[MapRequestPayload] CreateCommentRequest $dto): JsonResponse
    {
        $command = new CreateCommentCommand(
            $dto->content,
            $dto->taskId,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing comment.
     *
     * @param int $id
     * @param UpdateCommentRequest $dto
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateComment(int $id, #[MapRequestPayload] UpdateCommentRequest $dto): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::EDIT, $comment);

        $command = new UpdateCommentCommand(
            $id,
            $dto->content
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result);
    }

    /**
     * Deletes an existing comment.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::DELETE, $comment);

        $command = new DeleteCommentCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieves all comments.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllComments(Request $request): JsonResponse
    {
        $taskId = $request->query->get('task');
        $authorId = $request->query->get('author');

        $page = (int)$request->query->get('page');
        $limit = (int)$request->query->get('limit');

        $pagination = Pagination::create($page, $limit);
        $query = new GetAllCommentsQuery($taskId, $authorId, $this->getUser(), $pagination);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves a comment by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw new CommentNotFoundException();
        }

        $this->denyAccessUnlessGranted(CommentVoter::VIEW, $comment);

        $query = new GetCommentQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves comments for a given task.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    #[Route('/task/{taskId}', name: 'get_by_task', methods: ['GET'])]
    public function getCommentsByTask(int $taskId): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $query = new GetCommentsByTaskQuery($taskId, $this->getUser());
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }
}
