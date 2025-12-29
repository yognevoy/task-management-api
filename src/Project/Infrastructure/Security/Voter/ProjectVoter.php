<?php

namespace App\Project\Infrastructure\Security\Voter;

use App\Project\Domain\Entity\Project;
use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const VIEW_TASKS = 'view_tasks';
    public const ADD_MEMBER = 'add_member';
    public const REMOVE_MEMBER = 'remove_member';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::VIEW_TASKS,
            self::ADD_MEMBER,
            self::REMOVE_MEMBER
        ])) {
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

        return match ($attribute) {
            self::VIEW => $this->canView($user, $project),
            self::EDIT => $this->canEdit($user, $project),
            self::DELETE => $this->canDelete($user, $project),
            self::VIEW_TASKS => $this->canViewTasks($user, $project),
            self::ADD_MEMBER => $this->canAddMember($user, $project),
            self::REMOVE_MEMBER => $this->canRemoveMember($user, $project),
            default => false,
        };
    }

    /**
     * Checks if the current user can view the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canView(User $currentUser, Project $project): bool
    {
        return $project->isMember($currentUser) || $project->isOwner($currentUser);
    }

    /**
     * Checks if the current user can edit the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canEdit(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }

    /**
     * Checks if the current user can delete the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canDelete(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }

    /**
     * Checks if the current user can view tasks of the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */

    /**
     * Checks if the current user can view tasks of the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canViewTasks(User $currentUser, Project $project): bool
    {
        return $project->isMember($currentUser) || $project->getOwner() === $currentUser;
    }

    /**
     * Checks if the current user can add members to the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canAddMember(User $currentUser, Project $project): bool
    {
        return $project->getOwner() === $currentUser;
    }

    /**
     * Checks if the current user can remove members from the target project.
     *
     * @param User $currentUser
     * @param Project $project
     * @return bool
     */
    private function canRemoveMember(User $currentUser, Project $project): bool
    {
        if ($project->getOwner() === $currentUser) {
            return true;
        }

        return $project->getMembers()->contains($currentUser);
    }
}
