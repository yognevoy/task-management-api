<?php

namespace App\Task\Domain\Repository;

use App\Project\Domain\Entity\Project;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;

interface TaskRepositoryInterface
{
    /**
     * @return Task|null
     */
    public function find($id, $lockMode = null, $lockVersion = null);

    /**
     * @return Task[]
     */
    public function findAll();

    /**
     * @return Task[]
     */
    public function findByOwner(User $owner): array;

    /**
     * @return Task[]
     */
    public function findByParent(Task $parent): array;

    /**
     * @return Task[]
     */
    public function findByProject(Project $project): array;

    /**
     * @param User $owner
     * @return int
     */
    public function countByOwner(User $owner): int;
}
