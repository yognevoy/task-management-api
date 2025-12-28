<?php

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class QueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        $this->messageBus = $messageBus;
    }

    public function query(object $query): mixed
    {
        return $this->handle($query);
    }
}