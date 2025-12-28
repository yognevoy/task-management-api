<?php

namespace App\Task\Application\Query\GetAllTasks;

use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetAllTasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private TaskRepositoryInterface $taskRepository,
        private CacheInterface          $taskCache,
    )
    {
    }

    public function __invoke(GetAllTasksQuery $query): TaskListResponse
    {
        $currentUser = $query->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $cacheKey = 'tasks_user_' . $currentUser->getId();
        if ($currentUser->isAdmin()) {
            $cacheKey = 'tasks_all';
        }

        return $this->taskCache->get($cacheKey, function () use ($currentUser) {
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
            } else {
                $tasks = $this->taskRepository->findAll();
            }

            return new TaskListResponse($tasks);
        });
    }
}
