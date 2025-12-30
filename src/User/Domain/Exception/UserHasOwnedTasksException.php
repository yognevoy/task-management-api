<?php

namespace App\User\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserHasOwnedTasksException extends HttpException
{
    public function __construct(string $message = 'Cannot delete user who owns tasks', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(409, $message, $previous, [], $code);
    }
}
