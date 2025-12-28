<?php

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class CommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        $this->messageBus = $messageBus;
    }

    public function dispatch(object $command): mixed
    {
        return $this->handle($command);
    }
}