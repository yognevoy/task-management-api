<?php

namespace App\Tests\Functional\Task\Application\Controller;

use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\DataFixtures\TaskFixtures;
use App\Tests\Functional\BaseTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends BaseTestCase
{
    private const API_TASKS_URL = '/api/tasks';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            TaskFixtures::class,
        ]);
    }

    public function testCreateTaskSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'New Task',
            'description' => 'New task description',
            'status' => 'todo',
            'type' => 'feature',
            'priority' => 'medium',
        ];

        $client->request('POST', self::API_TASKS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $responseData);
        self::assertSame('New Task', $responseData['title']);
        self::assertSame('New task description', $responseData['description']);
        self::assertSame('todo', $responseData['status']);
        self::assertSame('feature', $responseData['type']);
        self::assertSame('medium', $responseData['priority']);
    }

    public function testCreateTaskWithInvalidDataReturnsError(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => '', // Invalid - empty title
            'description' => 'Valid description',
            'status' => 'invalid_status', // Invalid status
            'type' => 'feature',
            'priority' => 'medium',
        ];

        $client->request('POST', self::API_TASKS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetAllTasksSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::API_TASKS_URL);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['data']);
        self::assertGreaterThanOrEqual(2, $responseData['data']); // At least the fixtures
    }

    public function testGetTaskSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $taskFixture = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $client->request('GET', self::API_TASKS_URL . '/' . $taskFixture->getId());

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Test Task', $responseData['title']);
        self::assertSame('Test Task', $responseData['description']);
        self::assertSame('todo', $responseData['status']);
    }

    public function testGetNonExistentTaskReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentTaskId = 999999;
        $client->request('GET', self::API_TASKS_URL . '/' . $nonExistentTaskId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTaskSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $taskFixture = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $taskId = $taskFixture->getId();

        $data = [
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
            'status' => 'in_progress',
            'type' => 'bug',
            'priority' => 'high',
        ];

        $client->request('PUT', self::API_TASKS_URL . '/' . $taskId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Updated Task Title', $responseData['title']);
        self::assertSame('Updated task description', $responseData['description']);
        self::assertSame('in_progress', $responseData['status']);
        self::assertSame('bug', $responseData['type']);
        self::assertSame('high', $responseData['priority']);
    }

    public function testUpdateNonExistentTaskReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentTaskId = 999999;
        $data = [
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
            'status' => 'in_progress',
            'type' => 'bug',
            'priority' => 'high',
        ];

        $client->request('PUT', self::API_TASKS_URL . '/' . $nonExistentTaskId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteTaskSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $taskFixture = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $taskId = $taskFixture->getId();

        // Verify the task exists
        $client->request('GET', self::API_TASKS_URL . '/' . $taskId);
        self::assertResponseIsSuccessful();

        // Delete the task
        $client->request('DELETE', self::API_TASKS_URL . '/' . $taskId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify the task is deleted
        $client->request('GET', self::API_TASKS_URL . '/' . $taskId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentTaskReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentTaskId = 999999;
        $client->request('DELETE', self::API_TASKS_URL . '/' . $nonExistentTaskId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetSubtasksSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $taskFixture = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $taskId = $taskFixture->getId();

        $client->request('GET', self::API_TASKS_URL . '/' . $taskId . '/subtasks');

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['tasks']);
        self::assertEmpty($responseData['tasks']);
    }
}
