<?php

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;
use App\Entity\User;

interface TaskRepositoryInterface
{
    public function find($id, $lockMode = null, $lockVersion = null);

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
    public function findByProject(\App\Entity\Project $project): array;
}
