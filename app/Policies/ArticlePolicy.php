<?php

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
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
     * True for published articles; if unpublished, requires appropriate role/permission or author ownership.
     */
    public function view(?User $user, Article $article): bool
    {
        if ($article->status === PublicationStatus::Published && $article->published_at && $article->published_at->isPast()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        // Author ownership
        if ($article->author_id === $user->id) {
            return true;
        }

        return $user->can('articles.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('articles.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Rules:
     * - Super Admin & Chief Editor can update any article.
     * - Section Editor can update only if:
     *     1. They have 'articles.update'.
     *     2. The article belongs to a category assigned to them (or they are the author).
     *     3. The article is in 'draft' or 'review' status (cannot update once 'published' without Chief Editor privilege).
     */
    public function update(User $user, Article $article): bool
    {
        // Chief Editor has blanket update permission on any article
        if ($user->isChiefEditor()) {
            return true;
        }

        // Check if user has articles.update permission
        if (! $user->can('articles.update')) {
            return false;
        }

        // Cannot update once published/archived without Chief Editor privilege
        if (! in_array($article->status, [PublicationStatus::Draft, PublicationStatus::Review], true)) {
            return false;
        }

        // Must belong to an assigned category or be the author
        $isAuthor = $article->author_id === $user->id;
        $canManageCategory = $article->category && $user->canManageCategory($article->category);

        return $isAuthor || $canManageCategory;
    }

    /**
     * Determine whether the user can submit the article for review.
     */
    public function submitReview(User $user, Article $article): bool
    {
        if (! $user->can('articles.submit-review')) {
            return false;
        }

        if ($article->status !== PublicationStatus::Draft) {
            return false;
        }

        $isAuthor = $article->author_id === $user->id;
        $canManageCategory = $article->category && $user->canManageCategory($article->category);

        return $isAuthor || $canManageCategory;
    }

    /**
     * Determine whether the user can publish the model.
     * Requires 'articles.publish' permission (Chief Editor / Super Admin).
     */
    public function publish(User $user, Article $article): bool
    {
        return $user->can('articles.publish');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->can('articles.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article): bool
    {
        return $user->can('articles.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Article $article): bool
    {
        return $user->can('articles.delete');
    }
}
