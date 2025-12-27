<?php

namespace App\User\Domain\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';

    public static function toValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
