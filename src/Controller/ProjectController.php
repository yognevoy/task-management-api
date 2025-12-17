<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Exception\AccessDeniedException;
use App\Exception\ProjectHasTasksException;
use App\Exception\ProjectNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects', name: 'api_projects_')]
class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllProjects(Request $request): JsonResponse
    {
        $ownerId = $request->query->getInt('owner');

        if ($ownerId) {
            $user = $this->entityManager->getRepository(User::class)->find($ownerId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            $projects = $this->projectRepository->findByOwner($user);

            return $this->json($projects, context: ['groups' => 'project:read']);
        }

        $projects = $this->projectRepository->findAll();

        return $this->json($projects, context: ['groups' => 'project:read']);
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getProject(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        return $this->json($project, context: ['groups' => 'project:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createProject(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $project = new Project();
        $project->setTitle($data['title']);

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $project->setOwner($currentUser);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $this->json($project, Response::HTTP_CREATED, context: ['groups' => 'project:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateProject(int $id, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $project->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        $this->entityManager->flush();

        return $this->json($project, context: ['groups' => 'project:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteProject(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $taskCount = $this->projectRepository->countTasks($project);
        if ($taskCount > 0) {
            throw new ProjectHasTasksException();
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/tasks', name: 'get_project_tasks', methods: ['GET'])]
    public function getProjectTasks(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $tasks = $this->taskRepository->findBy(['project' => $project]);

        return $this->json($tasks, context: ['groups' => 'task:read']);
    }
}
