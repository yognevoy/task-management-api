<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    /**
     * @return User|null
     */
    public function find($id, $lockMode = null, $lockVersion = null);

    /**
     * @return User[]
     */
    public function findAll();

    /**
     * @return int
     */
    public function countAll(): int;

    /**
     * @return User|null
     */
    public function findOneByEmail(string $email): ?User;
}
