<?php

namespace App\Project\Application\Query\GetAllProjects;

use App\Project\Application\DTO\ProjectListResponse;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GetAllProjectsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface    $userRepository,
        private CacheInterface             $projectCache,
    )
    {
    }

    public function __invoke(GetAllProjectsQuery $query): ProjectListResponse
    {
        $cacheKey = $query->ownerId ? 'projects_user_' . $query->ownerId : 'projects_all';

        return $this->projectCache->get($cacheKey, function () use ($query) {
            if ($query->ownerId !== null) {
                $user = $this->userRepository->find($query->ownerId);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                $projects = $this->projectRepository->findByOwner($user);
            } else {
                $projects = $this->projectRepository->findAll();
            }

            return new ProjectListResponse($projects);
        });
    }
}
