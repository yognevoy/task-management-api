<?php

namespace App\Task\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MaxAssignedTasksReachedException extends HttpException
{
    public function __construct(int $maxTasks, \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(400, "User has reached the maximum number of assigned tasks ({$maxTasks})", $previous, [], $code);
    }
}
