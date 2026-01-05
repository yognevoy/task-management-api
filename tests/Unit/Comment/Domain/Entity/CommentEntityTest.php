<?php

namespace App\Tests\Unit\Comment\Domain\Entity;

use App\Comment\Domain\Entity\Comment;
use App\Tests\Trait\EntityFactoryTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CommentEntityTest extends TestCase
{
    use EntityFactoryTrait;

    public function testCommentCanBeCreatedWithDefaultValues(): void
    {
        $comment = new Comment();

        $this->assertNull($comment->getId());
        $this->assertNull($comment->getContent());
        $this->assertNotNull($comment->getCreatedAt());
        $this->assertNotNull($comment->getUpdatedAt());
    }

    public function testSetContentShouldSetContent(): void
    {
        $comment = new Comment();
        $content = 'Test Comment Content';

        $comment->setContent($content);

        $this->assertEquals($content, $comment->getContent());
    }

    public function testSetAuthorShouldSetAuthor(): void
    {
        $comment = new Comment();
        $user = $this->createUserWithId(1);

        $comment->setAuthor($user);

        $this->assertEquals($user, $comment->getAuthor());
        $this->assertEquals(1, $comment->getAuthorId());
    }

    public function testSetTaskShouldSetTask(): void
    {
        $comment = new Comment();
        $task = $this->createTaskWithId(1);

        $comment->setTask($task);

        $this->assertEquals($task, $comment->getTask());
        $this->assertEquals(1, $comment->getTaskId());
    }

    public function testUpdateTimestampsShouldUpdateUpdatedAt(): void
    {
        $comment = new Comment();
        $initialUpdatedAt = $comment->getUpdatedAt();

        usleep(100000);

        $comment->updateTimestamps();
        $updatedUpdatedAt = $comment->getUpdatedAt();

        $this->assertNotEquals($initialUpdatedAt, $updatedUpdatedAt);
    }

    public function testSetCreatedAtShouldSetCreatedAt(): void
    {
        $comment = new Comment();
        $createdAt = new DateTimeImmutable();

        $comment->setCreatedAt($createdAt);

        $this->assertEquals($createdAt, $comment->getCreatedAt());
    }

    public function testSetUpdatedAtShouldSetUpdatedAt(): void
    {
        $comment = new Comment();
        $updatedAt = new DateTimeImmutable();

        $comment->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $comment->getUpdatedAt());
    }
}
