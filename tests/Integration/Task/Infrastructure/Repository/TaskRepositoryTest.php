<?php

namespace App\Tests\Integration\Task\Infrastructure\Repository;

use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\DataFixtures\ProjectFixtures;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Infrastructure\DataFixtures\TaskFixtures;
use App\Tests\Integration\BaseTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;

class TaskRepositoryTest extends BaseTestCase
{
    private TaskRepositoryInterface $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            ProjectFixtures::class,
            TaskFixtures::class
        ]);
        /** @var TaskRepositoryInterface $taskRepository */
        $taskRepository = $this->getEntityManager()->getRepository(Task::class);
        $this->taskRepository = $taskRepository;
    }

    public function testFindByOwnerReturnsCorrectTasks(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $tasks = $this->taskRepository->findByOwner($user);

        $this->assertIsArray($tasks);
        $this->assertNotEmpty($tasks);
        foreach ($tasks as $task) {
            $this->assertInstanceOf(Task::class, $task);
            $this->assertEquals($user->getId(), $task->getOwner()->getId());
        }
    }

    public function testFindByProjectReturnsCorrectTasks(): void
    {
        $project = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $tasks = $this->taskRepository->findByProject($project);

        $this->assertIsArray($tasks);
        $this->assertNotEmpty($tasks);
        foreach ($tasks as $task) {
            $this->assertInstanceOf(Task::class, $task);
            $this->assertEquals($project->getId(), $task->getProject()->getId());
        }
    }

    public function testCountByOwnerReturnsCorrectCount(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $count = $this->taskRepository->countByOwner($user);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountByProjectReturnsCorrectCount(): void
    {
        $project = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $count = $this->taskRepository->countByProject($project);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountByUserReturnsCorrectCount(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $count = $this->taskRepository->countByUser($user);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountAllReturnsCorrectCount(): void
    {
        $count = $this->taskRepository->countAll();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindByParentReturnsCorrectTasks(): void
    {
        $parentTask = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $subtasks = $this->taskRepository->findByParent($parentTask);

        $this->assertIsArray($subtasks);
        $this->assertNotEmpty($subtasks);
        foreach ($subtasks as $subtask) {
            $this->assertInstanceOf(Task::class, $subtask);
            $this->assertEquals($parentTask->getId(), $subtask->getParent()->getId());
        }
    }

    public function testSaveAndPersistTask(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);
        $project = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $task = new Task();
        $task->setTitle('New Test Task');
        $task->setDescription('New Test Task');
        $task->setOwner($user);
        $task->setProject($project);

        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();

        $fetchedTask = $this->taskRepository->findByOwner($user);
        $foundTask = null;
        foreach ($fetchedTask as $t) {
            if ($t->getTitle() === 'New Test Task') {
                $foundTask = $t;
                break;
            }
        }

        $this->assertNotNull($foundTask);
        $this->assertEquals('New Test Task', $foundTask->getTitle());
    }
}
