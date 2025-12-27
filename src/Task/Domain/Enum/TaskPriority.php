<?php

namespace App\Task\Domain\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public static function toValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
