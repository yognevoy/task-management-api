<?php

namespace App\Shared\Application\Command;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}