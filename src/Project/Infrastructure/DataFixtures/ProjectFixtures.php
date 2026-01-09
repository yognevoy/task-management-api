<?php

namespace App\Project\Infrastructure\DataFixtures;

use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEST_PROJECT_REFERENCE = 'test-project';
    public const TEST_PROJECT_2_REFERENCE = 'test-project-2';

    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::TEST_USER_REFERENCE, User::class);
        $adminUser = $this->getReference(UserFixtures::ADMIN_USER_REFERENCE, User::class);

        $project = new Project();
        $project->setTitle('Test Project');
        $project->setDescription('Test Project Description');
        $project->setOwner($user);

        $manager->persist($project);

        $project2 = new Project();
        $project2->setTitle('Another Test Project');
        $project2->setDescription('Another Test Project');
        $project2->setOwner($adminUser);

        $manager->persist($project2);

        $manager->flush();

        $this->addReference(self::TEST_PROJECT_REFERENCE, $project);
        $this->addReference(self::TEST_PROJECT_2_REFERENCE, $project2);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
