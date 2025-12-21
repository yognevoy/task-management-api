<?php

namespace App\Project\Domain\Repository;

use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;

interface ProjectRepositoryInterface
{
    public function find($id, $lockMode = null, $lockVersion = null);

    public function findAll();

    /**
     * @return Project[]
     */
    public function findByOwner(User $owner): array;

    /**
     * Count tasks associated with a project
     */
    public function countTasks(Project $project): int;
}
