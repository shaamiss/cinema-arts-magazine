<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistProfileResource extends JsonResource
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
            'full_name' => $this->full_name,
            'slug' => $this->slug,
            'art_category' => $this->art_category instanceof \BackedEnum ? $this->art_category->value : $this->art_category,
            'biography' => $this->biography,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'death_date' => $this->death_date?->format('Y-m-d'),
            'avatar_url' => $this->avatar_url,
            'is_featured' => (bool) $this->is_featured,
            'articles_count' => $this->whenCounted('articles'),
            'articles' => ArticleListResource::collection($this->whenLoaded('articles')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
