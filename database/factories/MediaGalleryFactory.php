<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\MediaGallery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaGallery>
 */
class MediaGalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);
        $type = fake()->randomElement(MediaType::cases());

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'type' => $type,
            'file_path' => 'media/'.fake()->uuid().'.'.match ($type) {
                MediaType::Image => 'jpg',
                MediaType::Audio => 'mp3',
                MediaType::Video => 'mp4',
                MediaType::Pdf => 'pdf',
            },
            'thumbnail_path' => in_array($type, [MediaType::Image, MediaType::Video], true) ? 'thumbnails/'.fake()->uuid().'.jpg' : null,
            'duration_seconds' => in_array($type, [MediaType::Audio, MediaType::Video], true) ? fake()->numberBetween(30, 3600) : null,
            'size_bytes' => fake()->numberBetween(100_000, 50_000_000),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
