<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super-admin', 'chief-editor', 'section-editor', 'media-producer']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the articles authored by the user.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    /**
     * Get categories assigned to this user (for Section Editors).
     */
    public function managedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Alias for managedCategories.
     */
    public function categories(): BelongsToMany
    {
        return $this->managedCategories();
    }

    /**
     * Determine if the user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Determine if the user is a Chief Editor.
     */
    public function isChiefEditor(): bool
    {
        return $this->hasRole('chief-editor');
    }

    /**
     * Determine if the user is a Section Editor.
     */
    public function isSectionEditor(): bool
    {
        return $this->hasRole('section-editor');
    }

    /**
     * Determine if the user is a Media Producer.
     */
    public function isMediaProducer(): bool
    {
        return $this->hasRole('media-producer');
    }

    /**
     * Determine if the user can manage a specific category.
     */
    public function canManageCategory(Category $category): bool
    {
        if ($this->isSuperAdmin() || $this->isChiefEditor()) {
            return true;
        }

        if ($this->isSectionEditor()) {
            // Checks if user is assigned to this category or its parent category
            if ($this->relationLoaded('managedCategories')) {
                return $this->managedCategories->contains(function (Category $cat) use ($category) {
                    return $cat->id === $category->id || ($category->parent_id && $cat->id === $category->parent_id);
                });
            }

            return $this->managedCategories()
                ->where(function ($query) use ($category) {
                    $query->where('categories.id', $category->id);
                    if ($category->parent_id) {
                        $query->orWhere('categories.id', $category->parent_id);
                    }
                })
                ->exists();
        }

        return false;
    }
}
