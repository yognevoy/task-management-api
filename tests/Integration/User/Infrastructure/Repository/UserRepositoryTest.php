<?php

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Tests\Integration\BaseTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\DataFixtures\UserFixtures;

class UserRepositoryTest extends BaseTestCase
{
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([UserFixtures::class]);
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->getEntityManager()->getRepository(User::class);
        $this->userRepository = $userRepository;
    }

    public function testFindOneByEmailReturnsCorrectUser(): void
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');

        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testFindOneByEmailReturnsNullForNonExistentUser(): void
    {
        $user = $this->userRepository->findOneByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function testCountAllReturnsCorrectCount(): void
    {
        $count = $this->userRepository->countAll();

        $this->assertEquals(2, $count);
    }

    public function testSaveAndPersistUser(): void
    {
        $user = new User();
        $user->setEmail('test_new@example.com');
        $user->setPassword('password');

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $fetchedUser = $this->userRepository->findOneByEmail('test_new@example.com');

        $this->assertNotNull($fetchedUser);
        $this->assertEquals('test_new@example.com', $fetchedUser->getEmail());
    }
}
