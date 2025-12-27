<?php

namespace App\Task\Application\DTO;

use App\Task\Domain\Entity\Task;

class TaskListResponse
{
    /** @var TaskResponse[] */
    public array $tasks;
    public int $total;

    public function __construct(array $tasks, int $total)
    {
        $this->tasks = array_map(
            fn(Task $task) => TaskResponse::fromEntity($task),
            $tasks
        );
        $this->total = $total;
    }
}
