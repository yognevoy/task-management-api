<?php

namespace App\Task\Domain\Enum;

enum TaskType: string
{
    case TASK = 'task';
    case BUG = 'bug';
    case FEATURE = 'feature';

    public static function toValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
