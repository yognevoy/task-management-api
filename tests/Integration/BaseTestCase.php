<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base test case for integration tests.
 */
class BaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

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
     * Get the entity manager.
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
