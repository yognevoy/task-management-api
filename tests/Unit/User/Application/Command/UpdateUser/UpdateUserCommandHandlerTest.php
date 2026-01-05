<?php

namespace App\Tests\Unit\User\Application\Command\UpdateUser;

use App\Tests\Trait\EntityFactoryTrait;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Application\Command\UpdateUser\UpdateUserCommandHandler;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class UpdateUserCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private UpdateUserCommandHandler $handler;
    private UserPasswordHasherInterface|MockObject $passwordEncoder;
    private EntityManagerInterface|MockObject $entityManager;
    private UserRepositoryInterface|MockObject $userRepository;
    private UserCacheManager|MockObject $userCacheManager;
    private User $existingUser;

    protected function setUp(): void
    {
        $this->passwordEncoder = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userCacheManager = $this->createMock(UserCacheManager::class);

        $this->handler = new UpdateUserCommandHandler(
            $this->passwordEncoder,
            $this->entityManager,
            $this->userRepository,
            $this->userCacheManager
        );

        $this->existingUser = $this->createUserWithId(1);
        $this->existingUser->setEmail('old@example.com');
        $this->existingUser->setPassword('old_hashed_password');
        $this->existingUser->addRole('ROLE_USER');
    }

    public function testHandlerShouldUpdateUserSuccessfully(): void
    {
        $command = new UpdateUserCommand(
            1,
            'new@example.com',
            'new_password',
            ['ROLE_ADMIN']
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->passwordEncoder
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->equalTo($this->existingUser), $this->equalTo('new_password'))
            ->willReturn('new_hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingUser));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('new@example.com', $result->email);
        $this->assertContains('ROLE_ADMIN', $result->roles);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateUserCommand(999); // Non-existent user ID

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testHandlerShouldUpdateOnlyEmailWhenOnlyEmailProvided(): void
    {
        $command = new UpdateUserCommand(
            1,
            'updated@example.com'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->passwordEncoder
            ->expects($this->never())
            ->method('hashPassword');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingUser));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('updated@example.com', $result->email);
        $this->assertContains('ROLE_USER', $result->roles);
    }

    public function testHandlerShouldUpdateOnlyPasswordWhenOnlyPasswordProvided(): void
    {
        $command = new UpdateUserCommand(
            1,
            null,
            'updated_password'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->passwordEncoder
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->equalTo($this->existingUser), $this->equalTo('updated_password'))
            ->willReturn('updated_hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingUser));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('old@example.com', $result->email);
        $this->assertContains('ROLE_USER', $result->roles);
    }

    public function testHandlerShouldUpdateOnlyRolesWhenOnlyRolesProvided(): void
    {
        $command = new UpdateUserCommand(
            1,
            null,
            null,
            ['ROLE_SUPER_ADMIN', 'ROLE_USER']
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->passwordEncoder
            ->expects($this->never())
            ->method('hashPassword');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->equalTo($this->existingUser));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('old@example.com', $result->email);
        $this->assertContains('ROLE_SUPER_ADMIN', $result->roles);
        $this->assertContains('ROLE_USER', $result->roles);
    }
}
