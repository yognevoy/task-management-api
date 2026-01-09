<?php

namespace App\Comment\Infrastructure\DataFixtures;

use App\Comment\Domain\Entity\Comment;
use App\Task\Domain\Entity\Task;
use App\Task\Infrastructure\DataFixtures\TaskFixtures;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEST_COMMENT_REFERENCE = 'test-comment';
    public const TEST_COMMENT_2_REFERENCE = 'test-comment-2';

    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::TEST_USER_REFERENCE, User::class);
        $task = $this->getReference(TaskFixtures::TEST_TASK_REFERENCE, Task::class);

        $comment = new Comment();
        $comment->setContent('Test Comment');
        $comment->setAuthor($user);
        $comment->setTask($task);

        $manager->persist($comment);

        $comment2 = new Comment();
        $comment2->setContent('Another Test Comment');
        $comment2->setAuthor($user);
        $comment2->setTask($task);

        $manager->persist($comment2);

        $manager->flush();

        $this->addReference(self::TEST_COMMENT_REFERENCE, $comment);
        $this->addReference(self::TEST_COMMENT_2_REFERENCE, $comment2);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TaskFixtures::class,
        ];
    }
}
