<?php

namespace App\Tests\Integration\Project\Infrastructure\Repository;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Infrastructure\DataFixtures\ProjectFixtures;
use App\Tests\Integration\BaseTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;

class ProjectRepositoryTest extends BaseTestCase
{
    private ProjectRepositoryInterface $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            ProjectFixtures::class
        ]);
        /** @var ProjectRepositoryInterface $projectRepository */
        $projectRepository = $this->getEntityManager()->getRepository(Project::class);
        $this->projectRepository = $projectRepository;
    }

    public function testFindByOwnerReturnsCorrectProjects(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $projects = $this->projectRepository->findByOwner($user);

        $this->assertIsArray($projects);
        $this->assertNotEmpty($projects);
        foreach ($projects as $project) {
            $this->assertInstanceOf(Project::class, $project);
            $this->assertEquals($user->getId(), $project->getOwner()->getId());
        }
    }

    public function testCountByOwnerReturnsCorrectCount(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $count = $this->projectRepository->countByOwner($user);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountAllReturnsCorrectCount(): void
    {
        $count = $this->projectRepository->countAll();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testSaveAndPersistProject(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $project = new Project();
        $project->setTitle('New Test Project');
        $project->setDescription('New Test Project');
        $project->setOwner($user);

        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();

        $fetchedProject = $this->projectRepository->findByOwner($user);
        $foundProject = null;
        foreach ($fetchedProject as $p) {
            if ($p->getTitle() === 'New Test Project') {
                $foundProject = $p;
                break;
            }
        }

        $this->assertNotNull($foundProject);
        $this->assertEquals('New Test Project', $foundProject->getTitle());
    }
}
