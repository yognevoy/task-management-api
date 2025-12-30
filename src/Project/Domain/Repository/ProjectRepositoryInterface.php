<?php

namespace App\Project\Domain\Repository;

use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;

interface ProjectRepositoryInterface
{
    /**
     * @return Project|null
     */
    public function find($id, $lockMode = null, $lockVersion = null);

    /**
     * @return Project[]
     */
    public function findAll();

    /**
     * @return Project[]
     */
    public function findByOwner(User $owner): array;

    /**
     * @param User $owner
     * @return int
     */
    public function countByOwner(User $owner): int;
}
