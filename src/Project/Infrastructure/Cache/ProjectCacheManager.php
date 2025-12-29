<?php

namespace App\Project\Infrastructure\Cache;

use App\Project\Domain\Entity\Project;
use Symfony\Contracts\Cache\CacheInterface;

class ProjectCacheManager
{
    public function __construct(
        private CacheInterface $projectCache,
    )
    {
    }

    /**
     * Invalidates cache for a given project.
     *
     * @param Project $project
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateCache(Project $project): void
    {
        $this->projectCache->delete('project_' . $project->getId());
        $this->projectCache->delete('project_members_' . $project->getId());
        $this->projectCache->delete('projects_all');
        $this->projectCache->delete('projects_user_' . $project->getOwnerId());
    }
}
