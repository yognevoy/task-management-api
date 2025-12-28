<?php

namespace App\Project\Application\Query\GetProject;

use App\Project\Application\DTO\ProjectResponse;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetProjectQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CacheInterface             $projectCache,
    )
    {
    }

    public function __invoke(GetProjectQuery $query): ProjectResponse
    {
        $cacheKey = 'project_' . $query->id;

        return $this->projectCache->get($cacheKey, function () use ($query) {
            $project = $this->projectRepository->find($query->id);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            return ProjectResponse::fromEntity($project);
        });
    }
}
