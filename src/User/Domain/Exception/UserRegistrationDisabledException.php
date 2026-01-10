<?php

namespace App\User\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserRegistrationDisabledException extends HttpException
{
    public function __construct(\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(403, 'User registration is currently disabled', $previous, [], $code);
    }
}
