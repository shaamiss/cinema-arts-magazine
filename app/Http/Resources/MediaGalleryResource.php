<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaGalleryResource extends JsonResource
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
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'file_path' => $this->file_path,
            'thumbnail_path' => $this->thumbnail_path,
            'duration_seconds' => $this->duration_seconds ? (int) $this->duration_seconds : null,
            'size_bytes' => (int) $this->size_bytes,
            'size_formatted' => $this->formatBytes((int) $this->size_bytes),
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Format bytes into a human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
