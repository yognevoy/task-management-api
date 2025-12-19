<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const VIEW_TASKS = 'view_tasks';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::VIEW_TASKS])) {
            return false;
        }

        return $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $project = $subject;

        if ($user->isAdmin()) {
            return true;
        }

        return match($attribute) {
            self::VIEW => $this->canView($user, $project),
            self::EDIT => $this->canEdit($user, $project),
            self::DELETE => $this->canDelete($user, $project),
            self::VIEW_TASKS => $this->canViewTasks($user, $project),
            default => false,
        };
    }

    private function canView(User $currentUser, Project $project): bool
    {
        return true;
    }

    private function canEdit(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }

    private function canDelete(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }

    private function canViewTasks(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }
}
