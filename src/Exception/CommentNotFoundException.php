<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CommentNotFoundException extends HttpException
{
    public function __construct(string $message = 'Comment not found', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
