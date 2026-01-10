<?php

namespace App\Tests\Functional\Config\Application\Controller;

use App\Config\Infrastructure\DataFixtures\ConfigurationFixtures;
use App\Tests\Functional\BaseTestCase;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends BaseTestCase
{
    private const API_CONFIG_URL = '/api/config';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            ConfigurationFixtures::class,
        ]);
    }

    public function testSetConfigurationSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $data = [
            'configurations' => [
                'allow_user_registration' => 'false',
                'max_members_per_project' => '20',
            ]
        ];

        $client->request('POST', self::API_CONFIG_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($responseData);
        self::assertCount(2, $responseData);

        self::assertArrayHasKey(0, $responseData);
        self::assertArrayHasKey('key', $responseData[0]);
        self::assertArrayHasKey('value', $responseData[0]);
        self::assertEquals('allow_user_registration', $responseData[0]['key']);
        self::assertEquals('false', $responseData[0]['value']);

        self::assertArrayHasKey(1, $responseData);
        self::assertArrayHasKey('key', $responseData[1]);
        self::assertArrayHasKey('value', $responseData[1]);
        self::assertEquals('max_members_per_project', $responseData[1]['key']);
        self::assertEquals('20', $responseData[1]['value']);
    }

    public function testSetConfigurationWithInvalidDataReturnsError(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $data = [
            'configurations' => [
                'invalid_key' => 'some_value',
            ]
        ];

        $client->request('POST', self::API_CONFIG_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetConfigurationSuccessfully(): void
    {
        $client = $this->createAuthenticatedClient('admin@example.com');

        $client->request('GET', self::API_CONFIG_URL);

        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('configurations', $responseData);
        self::assertIsArray($responseData['configurations']);

        foreach ($responseData['configurations'] as $config) {
            self::assertArrayHasKey('key', $config);
            self::assertArrayHasKey('value', $config);

            self::assertContains($config['key'], [
                'allow_user_registration',
                'max_members_per_project',
                'max_assigned_tasks_per_user'
            ]);
        }
    }

    public function testSetConfigurationAsNonAdminReturnsError(): void
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'configurations' => [
                'allow_user_registration' => 'true',
            ]
        ];

        $client->request('POST', self::API_CONFIG_URL, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
