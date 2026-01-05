<?php

namespace App\Tests\Unit\Comment\Infrastructure\Security\Voter;

use App\Comment\Infrastructure\Security\Voter\CommentVoter;
use App\Tests\Trait\EntityFactoryTrait;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoterTest extends TestCase
{
    use EntityFactoryTrait;

    private CommentVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new CommentVoter();
    }

    public function testAdminUserCanViewComment(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $comment = $this->createCommentWithId(1);
        $author = $this->createUserWithId(2);
        $author->setEmail('author@example.com');
        $comment->setAuthor($author);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanEditComment(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $comment = $this->createCommentWithId(1);
        $author = $this->createUserWithId(2);
        $author->setEmail('author@example.com');
        $comment->setAuthor($author);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanDeleteComment(): void
    {
        $adminUser = $this->createUserWithId(1);
        $adminUser->setEmail('admin@example.com');
        $adminUser->addRole(UserRole::ADMIN);

        $comment = $this->createCommentWithId(1);
        $author = $this->createUserWithId(2);
        $author->setEmail('author@example.com');
        $comment->setAuthor($author);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($adminUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testCommentAuthorCanViewOwnComment(): void
    {
        $author = $this->createUserWithId(1);
        $author->setEmail('author@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($author);

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);

        $comment->setTask($task);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($author);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testCommentAuthorCanEditOwnComment(): void
    {
        $author = $this->createUserWithId(1);
        $author->setEmail('author@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($author);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($author);

        $result = $this->voter->vote($token, $comment, [CommentVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testCommentAuthorCanDeleteOwnComment(): void
    {
        $author = $this->createUserWithId(1);
        $author->setEmail('author@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($author);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($author);

        $result = $this->voter->vote($token, $comment, [CommentVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testTaskOwnerCanViewComment(): void
    {
        $taskOwner = $this->createUserWithId(1);
        $taskOwner->setEmail('task_owner@example.com');

        $commentAuthor = $this->createUserWithId(2);
        $commentAuthor->setEmail('comment_author@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);
        $comment->setTask($task);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($taskOwner);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectMemberCanViewComment(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $commentAuthor = $this->createUserWithId(2);
        $commentAuthor->setEmail('comment_author@example.com');

        $projectMember = $this->createUserWithId(3);
        $projectMember->setEmail('project_member@example.com');

        $taskOwner = $this->createUserWithId(4);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);
        $project->addMember($projectMember);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);
        $comment->setTask($task);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectMember);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testProjectOwnerCanViewComment(): void
    {
        $projectOwner = $this->createUserWithId(1);
        $projectOwner->setEmail('project_owner@example.com');

        $commentAuthor = $this->createUserWithId(2);
        $commentAuthor->setEmail('comment_author@example.com');

        $taskOwner = $this->createUserWithId(3);
        $taskOwner->setEmail('task_owner@example.com');

        $project = $this->createProjectWithId(1);
        $project->setOwner($projectOwner);

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);
        $task->setProject($project);

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);
        $comment->setTask($task);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($projectOwner);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testOtherUserCannotEditComment(): void
    {
        $commentAuthor = $this->createUserWithId(1);
        $commentAuthor->setEmail('comment_author@example.com');

        $otherUser = $this->createUserWithId(2);
        $otherUser->setEmail('other@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($otherUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testOtherUserCannotDeleteComment(): void
    {
        $commentAuthor = $this->createUserWithId(1);
        $commentAuthor->setEmail('comment_author@example.com');

        $otherUser = $this->createUserWithId(2);
        $otherUser->setEmail('other@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($otherUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testOtherUserCannotAccessComment(): void
    {
        $commentAuthor = $this->createUserWithId(1);
        $commentAuthor->setEmail('comment_author@example.com');

        $otherUser = $this->createUserWithId(2);
        $otherUser->setEmail('other@example.com');

        $comment = $this->createCommentWithId(1);
        $comment->setAuthor($commentAuthor);

        $taskOwner = $this->createUserWithId(2);
        $taskOwner->setEmail('task_owner@example.com');

        $task = $this->createTaskWithId(1);
        $task->setOwner($taskOwner);

        $comment->setTask($task);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($otherUser);

        $result = $this->voter->vote($token, $comment, [CommentVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }
}
