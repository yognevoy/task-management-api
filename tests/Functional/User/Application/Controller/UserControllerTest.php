<?php

namespace App\Tests\Functional\User\Application\Controller;

use App\Tests\Functional\BaseTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends BaseTestCase
{
    private const API_USERS_URL = '/api/users';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
        ]);
    }

    public function testUpdateUserSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $userFixture = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);

        $userId = $userFixture->getId();

        $data = [
            'email' => 'updated@example.com',
            'password' => 'newpassword',
            'roles' => ['ROLE_USER'],
        ];

        $client->request('PUT', self::API_USERS_URL . '/' . $userId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('updated@example.com', $responseData['email']);
        self::assertContains('ROLE_USER', $responseData['roles']);
    }

    public function testUpdateUserWithInvalidDataReturnsError(): void
    {
        $client = $this->createAuthenticatedClient();

        $userFixture = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);

        $userId = $userFixture->getId();

        $data = [
            'email' => '',
            'password' => 'short',
            'roles' => ['ROLE_INVALID'],
        ];

        $client->request('PUT', self::API_USERS_URL . '/' . $userId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateNonExistentUserReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentUserId = 999999;
        $data = [
            'email' => 'updated@example.com',
            'password' => 'newpassword',
            'roles' => ['ROLE_USER'],
        ];

        $client->request('PUT', self::API_USERS_URL . '/' . $nonExistentUserId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteUserSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $userFixture = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $userId = $userFixture->getId();

        // Verify the user exists
        $client->request('GET', self::API_USERS_URL . '/' . $userId);
        self::assertResponseIsSuccessful();

        // Delete the user
        $client->request('DELETE', self::API_USERS_URL . '/' . $userId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify the user is deleted
        $client->request('GET', self::API_USERS_URL . '/' . $userId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentUserReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $nonExistentUserId = 999999;
        $client->request('DELETE', self::API_USERS_URL . '/' . $nonExistentUserId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetAllUsersSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::API_USERS_URL);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData['data']);
        self::assertGreaterThanOrEqual(1, $responseData['data']);
    }

    public function testGetUserSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient();

        $userFixture = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);

        $userId = $userFixture->getId();

        $client->request('GET', self::API_USERS_URL . '/' . $userId);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('admin@example.com', $responseData['email']);
        self::assertContains('ROLE_ADMIN', $responseData['roles']);
    }

    public function testGetNonExistentUserReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();

        $nonExistentUserId = 999999;
        $client->request('GET', self::API_USERS_URL . '/' . $nonExistentUserId);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
