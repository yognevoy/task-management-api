<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use App\Exception\AccessDeniedException;
use App\Exception\CircularTaskReferenceException;
use App\Exception\ParentTaskNotFoundException;
use App\Exception\TaskNotFoundException;
use App\Exception\UserNotFoundException;
use App\Repository\TaskRepository;
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
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getAllTasks(Request $request): JsonResponse
    {
        $ownerId = $request->query->getInt('owner');

        if ($ownerId) {
            $user = $this->entityManager->getRepository(User::class)->find($ownerId);
            if (!$user) {
                throw new UserNotFoundException();
            }

            $tasks = $this->taskRepository->findByOwner($user);

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
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

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

        if (!empty($data['parentId'])) {
            $parentTask = $this->taskRepository->find($data['parentId']);
            if (!$parentTask) {
                throw new ParentTaskNotFoundException();
            }
            $task->setParent($parentTask);
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
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

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

        $this->entityManager->flush();

        return $this->json($task, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        $task = $this->taskRepository->find($id);

        if (!$task) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    public function getSubtasks(int $id): JsonResponse
    {
        $parentTask = $this->taskRepository->find($id);

        if (!$parentTask) {
            throw new TaskNotFoundException();
        }

        $subtasks = $this->taskRepository->findByParent($parentTask);

        return $this->json($subtasks, context: ['groups' => 'task:read']);
    }
}
