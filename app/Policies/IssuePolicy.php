<?php

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Published issues visible to all; drafts/review restricted to 'issues.view' or 'issues.view-any'.
     */
    public function view(?User $user, Issue $issue): bool
    {
        if ($issue->status === PublicationStatus::Published) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->can('issues.view') || $user->can('issues.view-any');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('issues.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Issue $issue): bool
    {
        return $user->can('issues.update');
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, Issue $issue): bool
    {
        return $user->can('issues.publish');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return $user->can('issues.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Issue $issue): bool
    {
        return $user->can('issues.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Issue $issue): bool
    {
        return $user->can('issues.delete');
    }
}
