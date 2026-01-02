<?php

namespace App\Project\Application\Query\GetProjectMembers;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Application\DTO\UserListResponse;

class GetProjectMembersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    )
    {
    }

    public function __invoke(GetProjectMembersQuery $query): UserListResponse
    {
        $project = $this->projectRepository->find($query->id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $members = $project->getMembers()->toArray();

        return new UserListResponse($members);
    }
}
