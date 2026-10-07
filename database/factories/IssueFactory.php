<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueNumber = fake()->unique()->numberBetween(1, 1000);
        $title = 'Issue #'.$issueNumber.' - '.fake()->catchPhrase();

        return [
            'issue_number' => $issueNumber,
            'title' => $title,
            'slug' => Str::slug($title),
            'cover_image' => 'covers/issue-'.$issueNumber.'.jpg',
            'pdf_file_path' => 'issues/issue-'.$issueNumber.'.pdf',
            'published_at' => fake()->date(),
            'status' => fake()->randomElement(PublicationStatus::cases()),
            'pages_count' => fake()->numberBetween(24, 120),
        ];
    }

    /**
     * Indicate that the issue is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublicationStatus::Published,
        ]);
    }
}
