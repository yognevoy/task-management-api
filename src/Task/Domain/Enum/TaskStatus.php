<?php

namespace App\Task\Domain\Enum;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';

    public static function toValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
