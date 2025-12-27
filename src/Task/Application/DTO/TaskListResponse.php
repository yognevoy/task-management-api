<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\Task;

class TaskListResponse
{
    /** @var TaskResponse[] */
    public array $tasks;

    public function __construct(array $tasks)
    {
        $this->tasks = array_map(
            fn(Task $task) => TaskResponse::fromEntity($task),
            $tasks
        );
    }
}
