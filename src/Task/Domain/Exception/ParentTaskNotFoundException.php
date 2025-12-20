<?php

namespace App\Task\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ParentTaskNotFoundException extends HttpException
{
    public function __construct(string $message = 'Parent task not found', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
