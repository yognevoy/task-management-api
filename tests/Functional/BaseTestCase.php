<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base test case for functional tests.
 */
class BaseTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get('default');
    }

    /**
     * Load fixtures for tests.
     *
     * @param array $classNames List of fixture classes to load
     */
    protected function loadFixtures(array $classNames): void
    {
        $this->databaseTool->loadFixtures($classNames);
    }

    /**
     * Create a client with authentication credentials.
     */
    protected function createAuthenticatedClient(string $email = 'test@example.com'): KernelBrowser
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'password',
            ])
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $data['token']);

        return $client;
    }

    /**
     * Create a client without authentication credentials.
     */
    protected function createUnauthenticatedClient(): KernelBrowser
    {
        return $this->client;
    }

    /**
     * Get the entity manager.
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
