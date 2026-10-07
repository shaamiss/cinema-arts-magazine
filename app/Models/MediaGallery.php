<?php

namespace App\Models;

use App\Enums\MediaType;
use Database\Factories\MediaGalleryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaGallery extends Model
{
    /** @use HasFactory<MediaGalleryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_galleries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'type',
        'file_path',
        'thumbnail_path',
        'duration_seconds',
        'size_bytes',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'duration_seconds' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * Scope a query to filter items by media type.
     */
    public function scopeOfType(Builder $query, MediaType|string $type): Builder
    {
        $value = $type instanceof MediaType ? $type->value : $type;

        return $query->where('type', $value);
    }
}
