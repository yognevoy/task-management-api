<?php

namespace App\Config\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ConfigurationNotFoundException extends HttpException
{
    public function __construct(string $key, \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, sprintf('Configuration with key "%s" not found.', $key), $previous, [], $code);
    }
}
