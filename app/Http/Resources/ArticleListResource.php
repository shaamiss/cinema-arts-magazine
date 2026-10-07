<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListResource extends JsonResource
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
            'featured_image' => $this->featured_image,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'views_count' => (int) $this->views_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'published_at_formatted' => $this->published_at?->translatedFormat('d F Y'),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
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
                ] : null;
            }),
            'artist_profile' => $this->whenLoaded('artistProfile', function () {
                return $this->artistProfile ? [
                    'id' => $this->artistProfile->id,
                    'full_name' => $this->artistProfile->full_name,
                    'slug' => $this->artistProfile->slug,
                    'art_category' => $this->artistProfile->art_category instanceof \BackedEnum
                        ? $this->artistProfile->art_category->value
                        : $this->artistProfile->art_category,
                ] : null;
            }),
        ];
    }
}
