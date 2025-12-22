<?php

namespace App\Task\Application\Controller;

use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\Security\Voter\TaskVoter;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Exception\CircularTaskReferenceException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Exception\TaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllTasks(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        if (!$currentUser->isAdmin()) {
            $qb = $this->entityManager->createQueryBuilder();
            $tasks = $qb
                ->select('t')
                ->from(Task::class, 't')
                ->leftJoin('t.project', 'p')
                ->where('t.owner = :user OR p.owner = :user')
                ->setParameter('user', $currentUser)
                ->orderBy('t.id', 'ASC')
                ->getQuery()
                ->getResult();

            return $this->json($tasks, context: ['groups' => 'task:read']);
        }

        $tasks = $this->taskRepository->findAll();

        return $this->json($tasks, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'get_one', methods: ['GET'])]
    public function getTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        return $this->json($task, context: ['groups' => 'task:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createTask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $task = new Task();
        $task->setTitle($data['title']);

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (!empty($data['status'])) {
            try {
                $status = TaskStatus::from($data['status']);
                $task->setStatus($status);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['type'])) {
            try {
                $type = TaskType::from($data['type']);
                $task->setType($type);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid type value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['priority'])) {
            try {
                $priority = TaskPriority::from($data['priority']);
                $task->setPriority($priority);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid priority value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['parentId'])) {
            $parentTask = $this->taskRepository->find($data['parentId']);

            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }
            $task->setParent($parentTask);
        }

        if (!empty($data['projectId'])) {
            $project = $this->entityManager->getRepository(\App\Entity\Project::class)->find($data['projectId']);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_BAD_REQUEST);
            }

            $currentUser = $this->getUser();

            // Check if the user owns the project
            if (!$currentUser instanceof User || $currentUser->getId() !== $project->getOwner()->getId()) {
                throw new AccessDeniedException();
            }

            $task->setProject($project);
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $task->setOwner($currentUser);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($task, Response::HTTP_CREATED, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateTask(int $id, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        if (!empty($data['status'])) {
            try {
                $status = TaskStatus::from($data['status']);
                $task->setStatus($status);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['type'])) {
            try {
                $type = TaskType::from($data['type']);
                $task->setType($type);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid type value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!empty($data['priority'])) {
            try {
                $priority = TaskPriority::from($data['priority']);
                $task->setPriority($priority);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Invalid priority value'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (array_key_exists('parentId', $data)) {
            if ($data['parentId'] === null || $data['parentId'] === 0) {
                $task->setParent(null);
            } else {
                $parentTask = $this->taskRepository->find($data['parentId']);

                if (!$parentTask) {
                    throw new ParentTaskNotFoundException();
                }

                if ($parentTask->getId() === $task->getId()) {
                    throw new CircularTaskReferenceException();
                }

                $task->setParent($parentTask);
            }
        }

        if (array_key_exists('projectId', $data)) {
            if ($data['projectId'] === null || $data['projectId'] === 0) {
                $task->setProject(null);
            } else {
                $project = $this->entityManager->getRepository(\App\Entity\Project::class)->find($data['projectId']);

                if (!$project) {
                    return $this->json(['error' => 'Project not found'], Response::HTTP_BAD_REQUEST);
                }

                $currentUser = $this->getUser();

                // Check if the user owns the project
                if (!$currentUser instanceof User || $currentUser->getId() !== $project->getOwner()->getId()) {
                    throw new AccessDeniedException();
                }

                $task->setProject($project);
            }
        }

        $this->entityManager->flush();

        return $this->json($task, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::DELETE, $task);

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    public function getSubtasks(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            throw new TaskNotFoundException();
        }

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        $subtasks = $this->taskRepository->findByParent($task);

        return $this->json($subtasks, context: ['groups' => 'task:read']);
    }
}
