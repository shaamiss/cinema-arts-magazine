<?php

namespace App\Policies;

use App\Models\MediaGallery;
use App\Models\User;

class MediaGalleryPolicy
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
     */
    public function view(?User $user, MediaGallery $mediaGallery): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('media.upload');
    }

    /**
     * Determine whether the user can upload media files.
     */
    public function upload(User $user): bool
    {
        return $user->can('media.upload');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaGallery $mediaGallery): bool
    {
        return $user->can('media.upload');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaGallery $mediaGallery): bool
    {
        return $user->can('media.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaGallery $mediaGallery): bool
    {
        return $user->can('media.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaGallery $mediaGallery): bool
    {
        return $user->can('media.delete');
    }
}
