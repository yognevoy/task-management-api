<?php

namespace App\Project\Application\Controller;

use App\Project\Application\Command\AddProjectMember\AddProjectMemberCommand;
use App\Project\Application\Command\CreateProject\CreateProjectCommand;
use App\Project\Application\Command\DeleteProject\DeleteProjectCommand;
use App\Project\Application\Command\RemoveProjectMember\RemoveProjectMemberCommand;
use App\Project\Application\Command\UpdateProject\UpdateProjectCommand;
use App\Project\Application\DTO\AddProjectMemberRequest;
use App\Project\Application\DTO\CreateProjectRequest;
use App\Project\Application\DTO\UpdateProjectRequest;
use App\Project\Application\Query\GetAllProjects\GetAllProjectsQuery;
use App\Project\Application\Query\GetProject\GetProjectQuery;
use App\Project\Application\Query\GetProjectMembers\GetProjectMembersQuery;
use App\Project\Application\Query\GetProjectTasks\GetProjectTasksQuery;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Security\Voter\ProjectVoter;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Domain\ValueObject\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects', name: 'api_projects_')]
class ProjectController extends AbstractController
{
    public function __construct(
        private CommandBusInterface        $commandBus,
        private QueryBusInterface          $queryBus,
        private ProjectRepositoryInterface $projectRepository,
    )
    {
    }

    /**
     * Creates a new project.
     *
     * @param CreateProjectRequest $dto
     * @return JsonResponse
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createProject(#[MapRequestPayload] CreateProjectRequest $dto): JsonResponse
    {
        $command = new CreateProjectCommand(
            $dto->title,
            $dto->description,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing project.
     *
     * @param int $id
     * @param UpdateProjectRequest $dto
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateProject(int $id, #[MapRequestPayload] UpdateProjectRequest $dto): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        $command = new UpdateProjectCommand(
            $id,
            $dto->title,
            $dto->description,
            $dto->ownerId
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result);
    }

    /**
     * Deletes an existing project.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteProject(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::DELETE, $project);

        $command = new DeleteProjectCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieves all projects.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllProjects(Request $request): JsonResponse
    {
        $ownerId = $request->query->get('owner');
        $page = (int)$request->query->get('page');
        $limit = (int)$request->query->get('limit');

        $pagination = Pagination::create($page, $limit);
        $query = new GetAllProjectsQuery($ownerId, $pagination);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves a project by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getProject(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $query = new GetProjectQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves tasks for a given project.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}/tasks', name: 'get_project_tasks', methods: ['GET'])]
    public function getProjectTasks(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::VIEW_TASKS, $project);

        $query = new GetProjectTasksQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Retrieves members for a given project.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}/members', name: 'get_project_members', methods: ['GET'])]
    public function getProjectMembers(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $query = new GetProjectMembersQuery($id);
        $result = $this->queryBus->query($query);

        return $this->json($result);
    }

    /**
     * Adds a member to a project.
     *
     * @param int $id
     * @param AddProjectMemberRequest $dto
     * @return JsonResponse
     */
    #[Route('/{id}/members', name: 'add_project_member', methods: ['POST'])]
    public function addProjectMember(int $id, #[MapRequestPayload] AddProjectMemberRequest $dto): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::ADD_MEMBER, $project);

        $command = new AddProjectMemberCommand(
            $id,
            $dto->userId
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result, Response::HTTP_CREATED);
    }

    /**
     * Removes a member from a project.
     *
     * @param int $id
     * @param int $userId
     * @return JsonResponse
     */
    #[Route('/{id}/members/{userId}', name: 'remove_project_member', methods: ['DELETE'])]
    public function removeProjectMember(int $id, int $userId): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::REMOVE_MEMBER, $project);

        $command = new RemoveProjectMemberCommand(
            $id,
            $userId,
            $this->getUser()
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json($result);
    }
}
