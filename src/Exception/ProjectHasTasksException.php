<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectHasTasksException extends HttpException
{
    public function __construct(string $message = 'Cannot delete project because it has associated tasks', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(409, $message, $previous, [], $code);
    }
}
