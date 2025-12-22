<?php

namespace App\Task\Application\Controller;

use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;
use App\Task\Domain\Enum\TaskStatus;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Domain\Exception\CircularTaskReferenceException;
use App\Task\Domain\Exception\ParentTaskNotFoundException;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Security\Voter\TaskVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[IsGranted(TaskVoter::VIEW, subject: 'task')]
    public function getTask(Task $task): JsonResponse
    {
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

        if (!empty($data['projectId'])) {
            $project = $this->entityManager->getRepository(\App\Entity\Project::class)->find($data['projectId']);
            if (!$project) {
                return $this->json(['error' => 'Project not found'], Response::HTTP_BAD_REQUEST);
            }

            // Check if the user owns the project
            $currentUser = $this->getUser();
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
    #[IsGranted(TaskVoter::EDIT, subject: 'task')]
    public function updateTask(Task $task, Request $request): JsonResponse
    {
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

        if (array_key_exists('projectId', $data)) {
            if ($data['projectId'] === null || $data['projectId'] === 0) {
                $task->setProject(null);
            } else {
                $project = $this->entityManager->getRepository(\App\Entity\Project::class)->find($data['projectId']);
                if (!$project) {
                    return $this->json(['error' => 'Project not found'], Response::HTTP_BAD_REQUEST);
                }

                // Check if the user owns the project
                $currentUser = $this->getUser();
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
    #[IsGranted(TaskVoter::DELETE, subject: 'task')]
    public function deleteTask(Task $task): JsonResponse
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/subtasks', name: 'get_subtasks', methods: ['GET'])]
    #[IsGranted(TaskVoter::VIEW, subject: 'parentTask')]
    public function getSubtasks(Task $task): JsonResponse
    {
        $subtasks = $this->taskRepository->findByParent($task);

        return $this->json($subtasks, context: ['groups' => 'task:read']);
    }
}
