<?php

namespace App\Project\Application\Controller;

use App\Project\Application\DTO\CreateProjectRequest;
use App\Project\Application\DTO\UpdateProjectRequest;
use App\Project\Application\Service\ProjectService;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\Security\Voter\ProjectVoter;
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
        private ProjectService             $projectService,
        private ProjectRepositoryInterface $projectRepository,
    )
    {
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
        $ownerId = $request->query->getInt('owner');

        return $this->json(
            $this->projectService->getAllProjects($ownerId)
        );
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
        // TODO: remove duplicated request
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->json(
            $this->projectService->getProjectById($id)
        );
    }

    /**
     * Creates a new project.
     *
     * @param CreateProjectRequest $dto
     * @return JsonResponse
     * @throws \App\Shared\Domain\Exception\ValidationException
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createProject(#[MapRequestPayload] CreateProjectRequest $dto): JsonResponse
    {
        return $this->json(
            $this->projectService->createProject(
                $dto, $this->getUser()
            ),
            Response::HTTP_CREATED
        );
    }

    /**
     * Updates an existing project.
     *
     * @param int $id
     * @param UpdateProjectRequest $dto
     * @return JsonResponse
     * @throws \App\Shared\Domain\Exception\ValidationException
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateProject(int $id, #[MapRequestPayload] UpdateProjectRequest $dto): JsonResponse
    {
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        return $this->json(
            $this->projectService->updateProject(
                $id, $dto, $this->getUser()
            )
        );
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

        $this->projectService->deleteProject($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
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

        return $this->json(
            $this->projectService->getProjectTasks($id)
        );
    }
}
