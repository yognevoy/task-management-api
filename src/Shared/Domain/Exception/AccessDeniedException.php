<?php

namespace App\Shared\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Access denied', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(403, $message, $previous, [], $code);
    }
}
