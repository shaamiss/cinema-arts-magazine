<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueDetailResource extends JsonResource
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
            'issue_number' => (int) $this->issue_number,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'pdf_file_path' => $this->pdf_file_path,
            'published_at' => $this->published_at?->format('Y-m-d'),
            'published_at_formatted' => $this->published_at?->translatedFormat('d F Y'),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'pages_count' => (int) $this->pages_count,
            'articles_count' => $this->relationLoaded('articles')
                ? $this->articles->count()
                : (int) ($this->articles_count ?? 0),
            'articles' => ArticleListResource::collection($this->whenLoaded('articles')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
