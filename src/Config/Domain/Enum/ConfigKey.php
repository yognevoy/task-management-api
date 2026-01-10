<?php

namespace App\Config\Domain\Enum;

enum ConfigKey: string
{
    case ALLOW_USER_REGISTRATION = 'allow_user_registration';
    case MAX_MEMBERS_PER_PROJECT = 'max_members_per_project';
    case MAX_ASSIGNED_TASKS_PER_USER = 'max_assigned_tasks_per_user';

    public static function toValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
