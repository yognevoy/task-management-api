<?php

namespace App\Project\Application\Query\GetProjectTasks;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Task\Application\DTO\TaskListResponse;
use App\Task\Domain\Repository\TaskRepositoryInterface;

class GetProjectTasksQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TaskRepositoryInterface    $taskRepository,
    )
    {
    }

    public function __invoke(GetProjectTasksQuery $query): TaskListResponse
    {
        $project = $this->projectRepository->find($query->id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $tasks = $this->taskRepository->findBy(['project' => $project]);

        return new TaskListResponse($tasks);
    }
}
