<?php

namespace App\Tests\Unit\User\Application\Query\GetUser;

use App\Tests\Trait\EntityFactoryTrait;
use App\User\Application\DTO\UserResponse;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Application\Query\GetUser\GetUserQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
class GetUserQueryHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private GetUserQueryHandler $handler;
    private UserRepositoryInterface|MockObject $userRepository;
    private CacheInterface|MockObject $userCache;
    private User $existingUser;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userCache = $this->createMock(CacheInterface::class);

        $this->handler = new GetUserQueryHandler(
            $this->userRepository,
            $this->userCache
        );

        $this->existingUser = $this->createUserWithId(1);
        $this->existingUser->setEmail('test@example.com');
        $this->existingUser->addRole('ROLE_USER');
    }

    public function testHandlerShouldReturnUserSuccessfully(): void
    {
        $query = new GetUserQuery(1);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingUser);

        $this->userCache
            ->expects($this->once())
            ->method('get')
            ->with('user_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = ($this->handler)($query);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertContains('ROLE_USER', $result->roles);
    }

    public function testHandlerShouldThrowUserNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $this->expectException(UserNotFoundException::class);

        $query = new GetUserQuery(999); // Non-existent user ID

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->userCache
            ->expects($this->once())
            ->method('get')
            ->with('user_999')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        ($this->handler)($query);
    }
}
