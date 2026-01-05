<?php

namespace App\Tests\Unit\Project\Application\Query\GetProject;

use App\Project\Application\DTO\ProjectResponse;
use App\Project\Application\Query\GetProject\GetProjectQuery;
use App\Project\Application\Query\GetProject\GetProjectQueryHandler;
use App\Project\Domain\Entity\Project;
use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
class GetProjectQueryHandlerTest extends TestCase
{
    use EntityFactoryTrait;

    private GetProjectQueryHandler $handler;
    private ProjectRepositoryInterface|MockObject $projectRepository;
    private CacheInterface|MockObject $projectCache;
    private User $currentUser;
    private Project $existingProject;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepositoryInterface::class);
        $this->projectCache = $this->createMock(CacheInterface::class);

        $this->handler = new GetProjectQueryHandler(
            $this->projectRepository,
            $this->projectCache
        );

        $this->currentUser = $this->createUserWithId(1);
        $this->existingProject = $this->createProjectWithId(1);
        $this->existingProject->setTitle('Test Project');
        $this->existingProject->setOwner($this->currentUser);
    }

    public function testHandlerShouldReturnProjectSuccessfully(): void
    {
        $query = new GetProjectQuery(1);

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->existingProject);

        $this->projectCache
            ->expects($this->once())
            ->method('get')
            ->with('project_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        $result = ($this->handler)($query);

        $this->assertInstanceOf(ProjectResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Project', $result->title);
    }

    public function testHandlerShouldThrowProjectNotFoundExceptionWhenProjectDoesNotExist(): void
    {
        $this->expectException(ProjectNotFoundException::class);

        $query = new GetProjectQuery(999); // Non-existent project ID

        $this->projectRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->projectCache
            ->expects($this->once())
            ->method('get')
            ->with('project_999')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });

        ($this->handler)($query);
    }
}
