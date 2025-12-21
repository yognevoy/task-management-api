<?php

namespace App\Project\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectNotFoundException extends HttpException
{
    public function __construct(string $message = 'Project not found', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
