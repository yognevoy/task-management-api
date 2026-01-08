<?php

namespace App\Task\Infrastructure\DataFixtures;

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

        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('Test Task');
        $task->setStatus(TaskStatus::TODO);
        $task->setType(TaskType::FEATURE);
        $task->setPriority(TaskPriority::MEDIUM);
        $task->setOwner($user);
        $task->setAssignee($user);

        $manager->persist($task);

        $task2 = new Task();
        $task2->setTitle('Another Test Task');
        $task2->setDescription('Another Test Task');
        $task2->setStatus(TaskStatus::IN_PROGRESS);
        $task2->setType(TaskType::BUG);
        $task2->setPriority(TaskPriority::HIGH);
        $task2->setOwner($user);
        $task2->setAssignee($user);

        $manager->persist($task2);

        $manager->flush();

        $this->addReference(self::TEST_TASK_REFERENCE, $task);
        $this->addReference(self::TEST_TASK_2_REFERENCE, $task2);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
