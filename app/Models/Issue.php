<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'issue_number',
        'title',
        'slug',
        'cover_image',
        'pdf_file_path',
        'published_at',
        'status',
        'pages_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_number' => 'integer',
            'published_at' => 'date',
            'status' => PublicationStatus::class,
            'pages_count' => 'integer',
        ];
    }

    /**
     * Get the articles included in this issue.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope a query to only include published issues.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Published);
    }
}
