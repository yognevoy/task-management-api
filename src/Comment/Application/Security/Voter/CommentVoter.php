<?php

namespace App\Comment\Application\Security\Voter;

use App\Comment\Domain\Entity\Comment;
use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        return $subject instanceof Comment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $comment = $subject;

        if ($user->isAdmin()) {
            return true;
        }

        return match($attribute) {
            self::VIEW => $this->canView($user, $comment),
            self::EDIT => $this->canEdit($user, $comment),
            self::DELETE => $this->canDelete($user, $comment),
            default => false,
        };
    }

    private function canView(User $currentUser, Comment $comment): bool
    {
        if ($comment->getTask()) {
            $task = $comment->getTask();
            if ($task->getOwner() === $currentUser) {
                return true;
            }
            if ($task->getProject() && $task->getProject()->getOwner() === $currentUser) {
                return true;
            }
        }

        return $comment->getAuthor() === $currentUser;
    }

    private function canEdit(User $currentUser, Comment $comment): bool
    {
        return $comment->getAuthor() === $currentUser;
    }

    private function canDelete(User $currentUser, Comment $comment): bool
    {
        return $comment->getAuthor() === $currentUser;
    }
}
