<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'views_count' => (int) $this->views_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_at_formatted' => $this->published_at?->translatedFormat('d F Y'),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            'issue' => $this->whenLoaded('issue', function () {
                return $this->issue ? [
                    'id' => $this->issue->id,
                    'title' => $this->issue->title,
                    'slug' => $this->issue->slug,
                    'issue_number' => $this->issue->issue_number,
                    'cover_image' => $this->issue->cover_image,
                ] : null;
            }),
            'artist_profile' => new ArtistProfileResource($this->whenLoaded('artistProfile')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
