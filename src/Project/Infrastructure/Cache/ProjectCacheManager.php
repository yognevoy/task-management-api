<?php

namespace App\Project\Infrastructure\Cache;

use App\Project\Domain\Entity\Project;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProjectCacheManager
{
    public function __construct(
        private TagAwareCacheInterface $projectCache,
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
        $this->projectCache->invalidateTags(['projects']);
        $this->projectCache->invalidateTags(['user_' . $project->getOwnerId()]);
        $this->projectCache->delete('project_' . $project->getId());
        $this->projectCache->delete('project_members_' . $project->getId());
    }
}
