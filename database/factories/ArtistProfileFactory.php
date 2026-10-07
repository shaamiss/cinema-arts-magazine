<?php

namespace Database\Factories;

use App\Enums\ArtCategory;
use App\Models\ArtistProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArtistProfile>
 */
class ArtistProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fullName = fake()->name();

        return [
            'full_name' => $fullName,
            'slug' => Str::slug($fullName).'-'.fake()->unique()->numberBetween(100, 9999),
            'art_category' => fake()->randomElement(ArtCategory::cases()),
            'biography' => fake()->paragraphs(3, true),
            'birth_date' => fake()->date('Y-m-d', '-30 years'),
            'death_date' => null,
            'avatar_url' => 'artists/'.Str::slug($fullName).'.jpg',
            'is_featured' => fake()->boolean(20),
        ];
    }

    /**
     * Indicate that the artist profile is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
