<?php

namespace App\Tests\Trait;

use App\Comment\Domain\Entity\Comment;
use App\Project\Domain\Entity\Project;
use App\Task\Domain\Entity\Task;
use App\User\Domain\Entity\User;

trait EntityFactoryTrait
{
    protected function createTaskWithId(int $id): Task
    {
        $task = new Task();

        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('id');
        $property->setValue($task, $id);

        return $task;
    }

    private function createUserWithId(int $id): User
    {
        $user = new User();

        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setValue($user, $id);

        return $user;
    }

    private function createProjectWithId(int $id): Project
    {
        $project = new Project();

        $reflection = new \ReflectionClass($project);
        $property = $reflection->getProperty('id');
        $property->setValue($project, $id);

        return $project;
    }

    private function createCommentWithId(int $id): Comment
    {
        $comment = new Comment();

        $reflection = new \ReflectionClass($comment);
        $property = $reflection->getProperty('id');
        $property->setValue($comment, $id);

        return $comment;
    }
}
