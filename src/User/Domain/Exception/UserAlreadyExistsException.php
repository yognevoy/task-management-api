<?php

namespace App\User\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserAlreadyExistsException extends HttpException
{
    public function __construct(string $message = 'User with this email already exists', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(409, $message, $previous, [], $code);
    }
}
