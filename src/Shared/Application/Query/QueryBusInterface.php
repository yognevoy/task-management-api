<?php

namespace App\Shared\Application\Query;

interface QueryBusInterface
{
    public function query(object $query): mixed;
}