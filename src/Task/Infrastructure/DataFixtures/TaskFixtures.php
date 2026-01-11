<?php

namespace App\Task\Infrastructure\DataFixtures;

use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\DataFixtures\ProjectFixtures;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskPriority;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Enum\TaskType;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEST_TASK_REFERENCE = 'test-task';
    public const TEST_TASK_2_REFERENCE = 'test-task-2';

    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::TEST_USER_REFERENCE, User::class);
        $project = $this->getReference(ProjectFixtures::TEST_PROJECT_REFERENCE, Project::class);
        $project2 = $this->getReference(ProjectFixtures::TEST_PROJECT_2_REFERENCE, Project::class);

        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('Test Task');
        $task->setStatus(TaskStatus::TODO);
        $task->setType(TaskType::FEATURE);
        $task->setPriority(TaskPriority::MEDIUM);
        $task->setOwner($user);
        $task->setAssignee($user);
        $task->setProject($project);

        $manager->persist($task);

        $task2 = new Task();
        $task2->setTitle('Another Test Task');
        $task2->setDescription('Another Test Task');
        $task2->setStatus(TaskStatus::IN_PROGRESS);
        $task2->setType(TaskType::BUG);
        $task2->setPriority(TaskPriority::HIGH);
        $task2->setOwner($user);
        $task2->setAssignee($user);
        $task2->setProject($project2);

        $manager->persist($task2);

        $subtask = new Task();
        $subtask->setTitle('Test Subtask');
        $subtask->setDescription('Test Subtask');
        $subtask->setStatus(TaskStatus::TODO);
        $subtask->setType(TaskType::TASK);
        $subtask->setPriority(TaskPriority::MEDIUM);
        $subtask->setOwner($user);
        $subtask->setAssignee($user);
        $subtask->setParent($task);
        $task->setProject($project);

        $manager->persist($subtask);

        $manager->flush();

        $this->addReference(self::TEST_TASK_REFERENCE, $task);
        $this->addReference(self::TEST_TASK_2_REFERENCE, $task2);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
