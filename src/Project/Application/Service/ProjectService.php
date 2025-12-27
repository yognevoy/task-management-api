<?php

namespace App\Project\Application\Service;

use App\Project\Application\DTO\CreateProjectRequest;
use App\Project\Application\DTO\ProjectListResponse;
use App\Project\Application\DTO\ProjectResponse;
use App\Project\Application\DTO\UpdateProjectRequest;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\ProjectHasTasksException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

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

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new \App\Shared\Domain\Exception\ValidationException($messages);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return ProjectResponse::fromEntity($project);
    }

    public function updateProject(int $id, UpdateProjectRequest $dto, ?User $currentUser = null): ProjectResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new \App\Project\Domain\Exception\ProjectNotFoundException();
        }

        if ($dto->title !== null) {
            $project->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $project->setDescription($dto->description);
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new \App\Shared\Domain\Exception\ValidationException($messages);
        }

        $this->entityManager->flush();

        return ProjectResponse::fromEntity($project);
    }

    public function deleteProject(Project $project): void
    {
        $taskCount = $this->projectRepository->countTasks($project);
        if ($taskCount > 0) {
            throw new ProjectHasTasksException();
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    public function getAllProjects(?int $ownerId = null): ProjectListResponse
    {
        $projects = [];

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
    }

    public function getProjectById(int $id): ProjectResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new \App\Project\Domain\Exception\ProjectNotFoundException();
        }

        return ProjectResponse::fromEntity($project);
    }

    public function getProjectTasks(int $id): TaskListResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project) {
            throw new \App\Project\Domain\Exception\ProjectNotFoundException();
        }

        $tasks = $this->taskRepository->findBy(['project' => $project]);

        return new TaskListResponse($tasks);
    }
}
