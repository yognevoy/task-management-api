<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CircularTaskReferenceException extends HttpException
{
    public function __construct(string $message = 'Task cannot be a parent of itself', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }
}
