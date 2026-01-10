<?php

namespace App\Config\Infrastructure\DataFixtures;

use App\Config\Domain\Entity\Configuration;
use App\Config\Domain\Enum\ConfigKey;
use App\Config\Domain\ValueObject\ConfigValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConfigurationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $maxMembersConfig = new Configuration(
            ConfigKey::MAX_MEMBERS_PER_PROJECT,
            ConfigValue::fromInt(100)
        );
        $manager->persist($maxMembersConfig);

        $maxAssignedTasksConfig = new Configuration(
            ConfigKey::MAX_ASSIGNED_TASKS_PER_USER,
            ConfigValue::fromInt(100)
        );
        $manager->persist($maxAssignedTasksConfig);

        $allowRegistrationConfig = new Configuration(
            ConfigKey::ALLOW_USER_REGISTRATION,
            ConfigValue::fromBool(true)
        );
        $manager->persist($allowRegistrationConfig);

        $manager->flush();
    }
}
