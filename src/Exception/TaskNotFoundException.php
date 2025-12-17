<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskNotFoundException extends HttpException
{
    public function __construct(string $message = 'Task not found', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
