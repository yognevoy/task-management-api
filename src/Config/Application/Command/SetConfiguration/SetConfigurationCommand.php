<?php

namespace App\Config\Application\Command\SetConfiguration;

use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\User;

class SetConfigurationCommand implements CommandInterface
{
    public function __construct(
        public readonly array $configurations,
        public readonly ?User $currentUser = null
    )
    {
    }
}
