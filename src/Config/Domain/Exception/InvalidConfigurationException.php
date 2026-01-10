<?php

namespace App\Config\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidConfigurationException extends HttpException
{
    public function __construct(string $key, \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(400, sprintf('Invalid configuration key "%s".', $key), $previous, [], $code);
    }
}
