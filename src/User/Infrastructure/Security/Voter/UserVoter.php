<?php

namespace App\User\Infrastructure\Security\Voter;

use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $targetUser = $subject;

        if ($user->isAdmin()) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user, $targetUser),
            self::EDIT => $this->canEdit($user, $targetUser),
            self::DELETE => $this->canDelete($user, $targetUser),
            default => false,
        };
    }

    private function canView(User $currentUser, User $targetUser): bool
    {
        return true;
    }

    private function canEdit(User $currentUser, User $targetUser): bool
    {
        return $currentUser === $targetUser;
    }

    private function canDelete(User $currentUser, User $targetUser): bool
    {
        return $currentUser === $targetUser;
    }
}
