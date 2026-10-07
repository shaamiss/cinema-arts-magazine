<?php

namespace App\Models;

use App\Enums\ArtCategory;
use Database\Factories\ArtistProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtistProfile extends Model
{
    /** @use HasFactory<ArtistProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'slug',
        'art_category',
        'biography',
        'birth_date',
        'death_date',
        'avatar_url',
        'is_featured',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'art_category' => ArtCategory::class,
            'birth_date' => 'date',
            'death_date' => 'date',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Get the articles featuring or written about this artist.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope a query to only include featured artist profiles.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by art category.
     */
    public function scopeCategory(Builder $query, ArtCategory|string $category): Builder
    {
        $value = $category instanceof ArtCategory ? $category->value : $category;

        return $query->where('art_category', $value);
    }
}
