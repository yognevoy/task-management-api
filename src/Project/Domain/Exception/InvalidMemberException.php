<?php

namespace App\Project\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidMemberException extends HttpException
{
    public function __construct(string $message = 'Invalid member operation', \Throwable $previous = null, int $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }

    public static function cannotAddOwnerAsMember(): self
    {
        return new self('Cannot add project owner as a member');
    }

    public static function cannotRemoveOwner(): self
    {
        return new self('Cannot remove owner from project');
    }

    public static function maxMembersReached(int $maxMembers): self
    {
        return new self("Maximum number of members ({$maxMembers}) reached for this project");
    }
}
