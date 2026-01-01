<?php

namespace App\Task\Application\Query\GetAllTasks;

use App\Shared\Application\DTO\PaginatedResponse;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\Query\TaskQueryBuilder;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetAllTasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepositoryInterface $taskRepository,
        private TaskQueryBuilder       $taskQueryBuilder,
        private TagAwareCacheInterface $taskCache,
    )
    {
    }

    public function __invoke(GetAllTasksQuery $query): PaginatedResponse
    {
        $currentUser = $query->currentUser;
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $pagination = $query->pagination;

        $cacheKey = sprintf(
            'tasks_user_%d_page_%d_limit_%d',
            $currentUser->getId(),
            $pagination->getPage(),
            $pagination->getLimit()
        );

        if ($currentUser->isAdmin()) {
            $cacheKey = sprintf(
                'tasks_all_page_%d_limit_%d',
                $pagination->getPage(),
                $pagination->getLimit()
            );
        }

        return $this->taskCache->get($cacheKey, function ($item) use ($currentUser, $pagination) {
            $item->tag(['user_' . $currentUser->getId()]);

            $qb = $this->entityManager->createQueryBuilder();

            if (!$currentUser->isAdmin()) {
                $this->taskQueryBuilder->buildForUser($qb, $currentUser);
            } else {
                $this->taskQueryBuilder->buildForAdmin($qb);
            }

            $total = $currentUser->isAdmin()
                ? $this->taskRepository->countAll()
                : $this->taskRepository->countByUser($currentUser);

            $tasks = $qb
                ->orderBy('t.id', 'ASC')
                ->setFirstResult($pagination->getOffset())
                ->setMaxResults($pagination->getLimit())
                ->getQuery()
                ->getResult();

            $taskListResponse = new TaskListResponse($tasks);
            return new PaginatedResponse($taskListResponse, $total, $pagination->getPage(), $pagination->getLimit());
        });
    }
}
