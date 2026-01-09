<?php

namespace App\Tests\Functional\Comment\Application\Controller;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Infrastructure\DataFixtures\CommentFixtures;
use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\DataFixtures\TaskFixtures;
use App\Tests\Functional\BaseTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class CommentControllerTest extends BaseTestCase
{
    private const API_COMMENTS_URL = '/api/comments';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            TaskFixtures::class,
            CommentFixtures::class,
        ]);
    }

    public function testCreateCommentSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $task = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $data = [
            'content' => 'New comment content',
            'taskId' => $task->getId(),
        ];

        $client->request('POST', self::API_COMMENTS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $responseData);
        self::assertSame('New comment content', $responseData['content']);
        self::assertArrayHasKey('authorId', $responseData);
        self::assertArrayHasKey('taskId', $responseData);
    }

    public function testCreateCommentWithInvalidDataReturnsError(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'content' => '',
            'taskId' => 999999,
        ];

        $client->request('POST', self::API_COMMENTS_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetAllCommentsSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::API_COMMENTS_URL);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['data']);
        self::assertGreaterThanOrEqual(2, $responseData['data']);
    }

    public function testGetCommentSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $commentFixture = $this->getEntityManager()->getRepository(Comment::class)
            ->findOneBy(['content' => 'Test Comment']);

        $commentId = $commentFixture->getId();

        $client->request('GET', self::API_COMMENTS_URL . '/' . $commentId);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Test Comment', $responseData['content']);
    }

    public function testGetNonExistentCommentReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentCommentId = 999999;
        $client->request('GET', self::API_COMMENTS_URL . '/' . $nonExistentCommentId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateCommentSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $commentFixture = $this->getEntityManager()->getRepository(Comment::class)
            ->findOneBy(['content' => 'Test Comment']);

        $commentId = $commentFixture->getId();

        $data = [
            'content' => 'Updated comment content',
        ];

        $client->request('PUT', self::API_COMMENTS_URL . '/' . $commentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Updated comment content', $responseData['content']);
    }

    public function testUpdateNonExistentCommentReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentCommentId = 999999;
        $data = [
            'content' => 'Updated comment content',
        ];

        $client->request('PUT', self::API_COMMENTS_URL . '/' . $nonExistentCommentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteCommentSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $commentFixture = $this->getEntityManager()->getRepository(Comment::class)
            ->findOneBy(['content' => 'Test Comment']);

        $commentId = $commentFixture->getId();

        // Verify the comment exists
        $client->request('GET', self::API_COMMENTS_URL . '/' . $commentId);
        self::assertResponseIsSuccessful();

        // Delete the comment
        $client->request('DELETE', self::API_COMMENTS_URL . '/' . $commentId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify the comment is deleted
        $client->request('GET', self::API_COMMENTS_URL . '/' . $commentId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentCommentReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentCommentId = 999999;
        $client->request('DELETE', self::API_COMMENTS_URL . '/' . $nonExistentCommentId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetCommentsByTaskSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $task = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $taskId = $task->getId();

        $client->request('GET', self::API_COMMENTS_URL . '/task/' . $taskId);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['comments']);
    }
}
