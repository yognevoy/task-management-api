<?php

namespace App\Tests\Functional\Project\Application\Controller;

use App\Project\Domain\Entity\Project;
use App\Project\Infrastructure\DataFixtures\ProjectFixtures;
use App\Tests\Functional\BaseTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class ProjectControllerTest extends BaseTestCase
{
    private const API_PROJECTS_URL = '/api/projects';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            ProjectFixtures::class,
        ]);
    }

    public function testCreateProjectSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'New Project',
            'description' => 'New project description',
        ];

        $client->request('POST', self::API_PROJECTS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $responseData);
        self::assertSame('New Project', $responseData['title']);
        self::assertSame('New project description', $responseData['description']);
        self::assertArrayHasKey('ownerId', $responseData);
    }

    public function testCreateProjectWithInvalidDataReturnsError(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => '',
            'description' => 'Valid description',
        ];

        $client->request('POST', self::API_PROJECTS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetAllProjectsSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::API_PROJECTS_URL);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['data']);
        self::assertGreaterThanOrEqual(2, $responseData['data']);
    }

    public function testGetProjectSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $projectFixture = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $projectId = $projectFixture->getId();

        $client->request('GET', self::API_PROJECTS_URL . '/' . $projectId);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Test Project', $responseData['title']);
        self::assertSame('Test Project Description', $responseData['description']);
    }

    public function testGetNonExistentProjectReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentProjectId = 999999;
        $client->request('GET', self::API_PROJECTS_URL . '/' . $nonExistentProjectId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateProjectSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $projectFixture = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $projectId = $projectFixture->getId();

        $data = [
            'title' => 'Updated Project Title',
            'description' => 'Updated project description',
            'ownerId' => $projectFixture->getOwnerId(),
        ];

        $client->request('PUT', self::API_PROJECTS_URL . '/' . $projectId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Updated Project Title', $responseData['title']);
        self::assertSame('Updated project description', $responseData['description']);
    }

    public function testUpdateNonExistentProjectReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentProjectId = 999999;
        $data = [
            'title' => 'Updated Project Title',
            'description' => 'Updated project description',
            'ownerId' => 1, // Some owner ID
        ];

        $client->request('PUT', self::API_PROJECTS_URL . '/' . $nonExistentProjectId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteProjectSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $projectFixture = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $projectId = $projectFixture->getId();

        // Verify the project exists
        $client->request('GET', self::API_PROJECTS_URL . '/' . $projectId);
        self::assertResponseIsSuccessful();

        // Delete the project
        $client->request('DELETE', self::API_PROJECTS_URL . '/' . $projectId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify the project is deleted
        $client->request('GET', self::API_PROJECTS_URL . '/' . $projectId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentProjectReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentProjectId = 999999;
        $client->request('DELETE', self::API_PROJECTS_URL . '/' . $nonExistentProjectId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetProjectTasksSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $projectFixture = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $projectId = $projectFixture->getId();

        $client->request('GET', self::API_PROJECTS_URL . '/' . $projectId . '/tasks');

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['tasks']);
    }

    public function testGetProjectMembersSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $projectFixture = $this->getEntityManager()->getRepository(Project::class)
            ->findOneBy(['title' => 'Test Project']);

        $projectId = $projectFixture->getId();

        $client->request('GET', self::API_PROJECTS_URL . '/' . $projectId . '/members');

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['users']);
    }
}
