<?php

namespace App\Shared\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidPaginationException extends HttpException
{
    public function __construct(string $message = 'Invalid pagination parameters', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }

    public static function invalidPage(): self
    {
        return new self('Page must be greater than or equal to 1');
    }

    public static function invalidLimit(): self
    {
        return new self('Limit must be between 1 and 100');
    }
}
