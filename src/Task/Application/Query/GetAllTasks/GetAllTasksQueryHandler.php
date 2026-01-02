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

class GetAllTasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private TaskRepositoryInterface $taskRepository,
        private TaskQueryBuilder        $taskQueryBuilder,
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

        $qb = $this->entityManager->createQueryBuilder();

        if ($currentUser->isAdmin()) {
            $this->taskQueryBuilder->buildForAdmin($qb);
            $total = $this->taskRepository->countAll();
        } else {
            $this->taskQueryBuilder->buildForUser($qb, $currentUser);
            $total = $this->taskRepository->countByUser($currentUser);
        }

        $tasks = $qb
            ->orderBy('t.id', 'ASC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->getQuery()
            ->getResult();

        $taskListResponse = new TaskListResponse($tasks);
        return new PaginatedResponse($taskListResponse, $total, $pagination->getPage(), $pagination->getLimit());
    }
}
