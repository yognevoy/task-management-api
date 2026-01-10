<?php

namespace App\Tests\Unit\User\Application\Command\RegisterUser;

use App\Config\Application\Service\ConfigurationService;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Application\DTO\UserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserRegistrationDisabledException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Cache\UserCacheManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class RegisterUserCommandHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private RegisterUserCommandHandler $handler;
    private UserPasswordHasherInterface|MockObject $passwordEncoder;
    private EntityManagerInterface|MockObject $entityManager;
    private UserRepositoryInterface|MockObject $userRepository;
    private UserCacheManager|MockObject $userCacheManager;
    private ConfigurationService|MockObject $configurationService;

    protected function setUp(): void
    {
        $this->passwordEncoder = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userCacheManager = $this->createMock(UserCacheManager::class);
        $this->configurationService = $this->createMock(ConfigurationService::class);

        $this->handler = new RegisterUserCommandHandler(
            $this->passwordEncoder,
            $this->entityManager,
            $this->userRepository,
            $this->userCacheManager,
            $this->configurationService
        );
    }

    public function testHandlerShouldRegisterUserSuccessfully(): void
    {
        $command = new RegisterUserCommand(
            'test@example.com',
            'password123',
            ['ROLE_USER']
        );

        $this->configurationService
            ->expects($this->once())
            ->method('isUserRegistrationAllowed')
            ->willReturn(true);

        $user = null;
        $persistCallback = function ($persistedUser) use (&$user) {
            $user = $persistedUser;
        };

        $flushCallback = function () use (&$user) {
            if ($user !== null) {
                $reflection = new \ReflectionClass($user);
                $property = $reflection->getProperty('id');
                $property->setValue($user, 1);
            }
        };

        $this->passwordEncoder
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'password123')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($user) {
                return $user instanceof User && $user->getId() === 1;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertContains('ROLE_USER', $result->roles);
    }

    public function testHandlerShouldRegisterUserWithDefaultRoles(): void
    {
        $command = new RegisterUserCommand(
            'test2@example.com',
            'password123'
        // No roles specified
        );

        $this->configurationService
            ->expects($this->once())
            ->method('isUserRegistrationAllowed')
            ->willReturn(true);

        $user = null;
        $persistCallback = function ($persistedUser) use (&$user) {
            $user = $persistedUser;
        };

        $flushCallback = function () use (&$user) {
            if ($user !== null) {
                $reflection = new \ReflectionClass($user);
                $property = $reflection->getProperty('id');
                $property->setValue($user, 2);
            }
        };

        $this->passwordEncoder
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'password123')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class))
            ->willReturnCallback($persistCallback);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback($flushCallback);

        $this->userCacheManager
            ->expects($this->once())
            ->method('invalidateCache')
            ->with($this->callback(function ($user) {
                return $user instanceof User && $user->getId() === 2;
            }));

        $result = ($this->handler)($command);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('test2@example.com', $result->email);
        $this->assertEquals(['ROLE_USER'], $result->roles);
    }

    public function testHandlerShouldThrowUserRegistrationDisabledExceptionWhenRegistrationIsDisabled(): void
    {
        $command = new RegisterUserCommand(
            'test@example.com',
            'password123',
            ['ROLE_USER']
        );

        $this->configurationService
            ->expects($this->once())
            ->method('isUserRegistrationAllowed')
            ->willReturn(false);

        $this->expectException(UserRegistrationDisabledException::class);

        ($this->handler)($command);
    }
}
