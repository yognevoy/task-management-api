<?php

namespace App\Tests\Functional\User\Application\Controller;

use App\Tests\Functional\BaseTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends BaseTestCase
{
    private const API_REGISTER_URL = '/api/register';
    private const API_LOGIN_URL = '/api/login_check';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
        ]);
    }

    public function testRegisterUserSuccessfully(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => 'newuser@example.com',
            'password' => 'password',
            'roles' => ['ROLE_USER'],
        ];

        $client->request('POST', self::API_REGISTER_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('User registered successfully', $responseData['message']);
        self::assertArrayHasKey('user', $responseData);
        self::assertSame('newuser@example.com', $responseData['user']['email']);
        self::assertContains('ROLE_USER', $responseData['user']['roles']);
    }

    public function testRegisterUserWithInvalidDataReturnsError(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => '',
            'password' => '123',
            'roles' => ['ROLE_INVALID'],
        ];

        $client->request('POST', self::API_REGISTER_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithDuplicateEmailReturnsError(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => 'admin@example.com',
            'password' => 'anotherpassword',
            'roles' => ['ROLE_USER'],
        ];

        $client->request('POST', self::API_REGISTER_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testLoginSuccessfully(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => 'admin@example.com',
            'password' => 'password',
        ];

        $client->request('POST', self::API_LOGIN_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $responseData);
        self::assertIsString($responseData['token']);
        self::assertNotEmpty($responseData['token']);
    }

    public function testLoginWithInvalidCredentialsReturnsError(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $client->request('POST', self::API_LOGIN_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithEmptyCredentialsReturnsError(): void
    {
        $client = $this->createUnauthenticatedClient();

        $data = [
            'email' => '',
            'password' => '',
        ];

        $client->request('POST', self::API_LOGIN_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
