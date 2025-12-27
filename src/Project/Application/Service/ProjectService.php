<?php

namespace App\Project\Application\Service;

use App\Project\Application\DTO\CreateProjectRequest;
use App\Project\Application\DTO\ProjectListResponse;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Application\DTO\UpdateProjectRequest;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\ProjectHasTasksException;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TaskRepositoryInterface    $taskRepository,
        private UserRepositoryInterface    $userRepository,
        private EntityManagerInterface     $entityManager,
        private ValidatorInterface         $validator,
        private CacheInterface             $projectCache,
    )
    {
    }

    /**
     * Creates a new project.
     *
     * @param CreateProjectRequest $dto
     * @param User|null $currentUser
     * @return ProjectResponse
     * @throws \App\Shared\Domain\Exception\ValidationException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function createProject(CreateProjectRequest $dto, ?User $currentUser = null): ProjectResponse
    {
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $project = new Project();
        $project->setTitle($dto->title);

        if ($dto->description !== null) {
            $project->setDescription($dto->description);
        }

        $project->setOwner($currentUser);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }

    /**
     * Updates an existing project.
     *
     * @param int $id
     * @param UpdateProjectRequest $dto
     * @param User|null $currentUser
     * @return ProjectResponse
     * @throws \App\Shared\Domain\Exception\ValidationException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateProject(int $id, UpdateProjectRequest $dto, ?User $currentUser = null): ProjectResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        if ($dto->title !== null) {
            $project->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $project->setDescription($dto->description);
        }

        $this->entityManager->flush();

        $this->invalidateCache($project);

        return ProjectResponse::fromEntity($project);
    }

    /**
     * Deletes an existing project.
     *
     * @param Project $project
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteProject(Project $project): void
    {
        $taskCount = $this->projectRepository->countTasks($project);
        if ($taskCount > 0) {
            throw new ProjectHasTasksException();
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        $this->invalidateCache($project);
    }

    /**
     * Retrieves all projects.
     *
     * @param int|null $ownerId
     * @return ProjectListResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllProjects(?int $ownerId = null): ProjectListResponse
    {
        $cacheKey = $ownerId ? 'projects_user_' . $ownerId : 'projects_all';

        return $this->projectCache->get($cacheKey, function () use ($ownerId) {
            if ($ownerId !== null) {
                $user = $this->userRepository->find($ownerId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                $projects = $this->projectRepository->findByOwner($user);
            } else {
                $projects = $this->projectRepository->findAll();
            }

            return new ProjectListResponse($projects);
        });
    }

    /**
     * Retrieves a project by its ID.
     *
     * @param int $id
     * @return ProjectResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getProjectById(int $id): ProjectResponse
    {
        $cacheKey = 'project_' . $id;

        return $this->projectCache->get($cacheKey, function () use ($id) {
            $project = $this->projectRepository->find($id);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            return ProjectResponse::fromEntity($project);
        });
    }

    /**
     * Retrieves tasks for a given project.
     *
     * @param int $id
     * @return TaskListResponse
     */
    public function getProjectTasks(int $id): TaskListResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $tasks = $this->taskRepository->findBy(['project' => $project]);

        return new TaskListResponse($tasks);
    }

    /**
     * Invalidates cache for a given project.
     *
     * @param Project $project
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateCache(Project $project): void
    {
        $this->projectCache->delete('project_' . $project->getId());
        $this->projectCache->delete('projects_all');
        $this->projectCache->delete('projects_user_' . $project->getOwnerId());
    }
}
