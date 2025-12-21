<?php

namespace App\User\Domain\Repository;

interface UserRepositoryInterface
{
    public function find($id, $lockMode = null, $lockVersion = null);

    public function findAll();
}
