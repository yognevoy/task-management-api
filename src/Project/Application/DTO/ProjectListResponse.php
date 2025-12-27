<?php

namespace App\Project\Application\DTO;

use App\Project\Domain\Entity\Project;

class ProjectListResponse
{
    public array $projects;

    public function __construct(array $projects)
    {
        $this->projects = array_map(function ($project) {
            return ProjectResponse::fromEntity($project);
        }, $projects);
    }
}
