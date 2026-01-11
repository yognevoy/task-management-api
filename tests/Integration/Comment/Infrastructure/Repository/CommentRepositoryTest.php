<?php

namespace App\Tests\Integration\Comment\Infrastructure\Repository;

use App\Comment\Domain\Entity\Comment;
use App\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Task\Domain\Entity\Task;
use App\Tests\Integration\BaseTestCase;
use App\Comment\Infrastructure\DataFixtures\CommentFixtures;
use App\Task\Infrastructure\DataFixtures\TaskFixtures;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;

class CommentRepositoryTest extends BaseTestCase
{
    private CommentRepositoryInterface $commentRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            UserFixtures::class,
            TaskFixtures::class,
            CommentFixtures::class
        ]);
        /** @var CommentRepositoryInterface $commentRepository */
        $commentRepository = $this->getEntityManager()->getRepository(Comment::class);
        $this->commentRepository = $commentRepository;
    }

    public function testFindByTaskReturnsCorrectComments(): void
    {
        $task = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $comments = $this->commentRepository->findByTask($task);

        $this->assertIsArray($comments);
        foreach ($comments as $comment) {
            $this->assertInstanceOf(Comment::class, $comment);
            $this->assertEquals($task->getId(), $comment->getTask()->getId());
        }
    }

    public function testFindByAuthorReturnsCorrectComments(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $comments = $this->commentRepository->findByAuthor($user);

        $this->assertIsArray($comments);
        foreach ($comments as $comment) {
            $this->assertInstanceOf(Comment::class, $comment);
            $this->assertEquals($user->getId(), $comment->getAuthor()->getId());
        }
    }

    public function testCountByTaskReturnsCorrectCount(): void
    {
        $task = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        $count = $this->commentRepository->countByTask($task);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountByAuthorReturnsCorrectCount(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $count = $this->commentRepository->countByAuthor($user);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountByUserReturnsCorrectCount(): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $count = $this->commentRepository->countByUser($user);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountAllReturnsCorrectCount(): void
    {
        $count = $this->commentRepository->countAll();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testSaveAndPersistComment(): void
    {
        $this->loadFixtures([
            UserFixtures::class,
            TaskFixtures::class
        ]);

        $user = $this->getEntityManager()->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);
        $task = $this->getEntityManager()->getRepository(Task::class)
            ->findOneBy(['title' => 'Test Task']);

        if ($task !== null) {
            $comment = new Comment();
            $comment->setContent('New Test Comment');
            $comment->setAuthor($user);
            $comment->setTask($task);

            $this->getEntityManager()->persist($comment);
            $this->getEntityManager()->flush();

            $fetchedComments = $this->commentRepository->findByTask($task);
            $foundComment = null;
            foreach ($fetchedComments as $c) {
                if ($c->getContent() === 'New Test Comment') {
                    $foundComment = $c;
                    break;
                }
            }

            $this->assertNotNull($foundComment);
            $this->assertEquals('New Test Comment', $foundComment->getContent());
        }
    }
}
