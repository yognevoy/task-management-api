<?php

namespace App\User\Infrastructure\DataFixtures;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const TEST_USER_REFERENCE = 'test-user';
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordEncoder->hashPassword($user, 'password'));
        $user->setRoles([UserRole::USER]);

        $manager->persist($user);

        $user2 = new User();
        $user2->setEmail('admin@example.com');
        $user2->setPassword($this->passwordEncoder->hashPassword($user2, 'password'));
        $user2->setRoles([UserRole::ADMIN]);

        $manager->persist($user2);

        $manager->flush();

        $this->addReference(self::TEST_USER_REFERENCE, $user);
        $this->addReference(self::ADMIN_USER_REFERENCE, $user2);
    }
}
