<?php

namespace App\Project\Application\Query\GetProjectMembers;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Application\DTO\UserListResponse;
use Symfony\Contracts\Cache\CacheInterface;

class GetProjectMembersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CacheInterface             $projectCache,
    )
    {
    }

    public function __invoke(GetProjectMembersQuery $query): UserListResponse
    {
        $cacheKey = 'project_members_' . $query->id;

        return $this->projectCache->get($cacheKey, function () use ($query) {
            $project = $this->projectRepository->find($query->id);
            if (!$project) {
                throw new ProjectNotFoundException();
            }

            $members = $project->getMembers()->toArray();

            return new UserListResponse($members);
        });
    }
}
