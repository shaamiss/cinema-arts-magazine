<?php

namespace Tests\Feature;

use App\Enums\ArtCategory;
use App\Enums\MediaType;
use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Issue;
use App\Models\MediaGallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalMagazineEntitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_support_parent_child_hierarchy(): void
    {
        $parent = Category::create([
            'name' => 'Cinema',
            'slug' => 'cinema',
            'description' => 'Cinema category',
        ]);

        $child = Category::create([
            'name' => 'Documentary Cinema',
            'slug' => 'documentary-cinema',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertCount(1, $parent->children);
        $this->assertTrue($parent->children->first()->is($child));
        $this->assertCount(1, Category::root()->get());
    }

    public function test_issue_has_proper_casts_and_can_have_articles(): void
    {
        $issue = Issue::create([
            'issue_number' => 12,
            'title' => 'Issue 12',
            'slug' => 'issue-12',
            'cover_image' => 'covers/12.jpg',
            'pdf_file_path' => 'pdfs/12.pdf',
            'published_at' => '2026-05-01',
            'status' => PublicationStatus::Published,
            'pages_count' => 64,
        ]);

        $this->assertSame(12, $issue->issue_number);
        $this->assertSame(PublicationStatus::Published, $issue->status);
        $this->assertSame('2026-05-01', $issue->published_at->format('Y-m-d'));
        $this->assertSame(64, $issue->pages_count);
    }

    public function test_artist_profile_casts_art_category_enum_and_dates(): void
    {
        $artist = ArtistProfile::create([
            'full_name' => 'Mohamed Zwawi',
            'slug' => 'mohamed-zwawi',
            'art_category' => ArtCategory::FineArts,
            'biography' => 'Famous caricaturist',
            'birth_date' => '1936-01-01',
            'death_date' => '2011-06-05',
            'is_featured' => true,
        ]);

        $this->assertSame(ArtCategory::FineArts, $artist->art_category);
        $this->assertTrue($artist->is_featured);
        $this->assertSame('1936-01-01', $artist->birth_date->format('Y-m-d'));
        $this->assertSame('2011-06-05', $artist->death_date->format('Y-m-d'));
        $this->assertCount(1, ArtistProfile::featured()->get());
    }

    public function test_article_relationships_and_foreign_key_on_delete_rules(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->create();
        $issue = Issue::factory()->published()->create();
        $artist = ArtistProfile::factory()->create();

        $article = Article::create([
            'title' => 'Spotlight on Fine Arts',
            'slug' => 'spotlight-on-fine-arts',
            'excerpt' => 'An excerpt',
            'content' => 'Full long text article content here',
            'category_id' => $category->id,
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'artist_profile_id' => $artist->id,
            'status' => PublicationStatus::Published,
            'views_count' => 150,
            'published_at' => now()->subDay(),
        ]);

        // Test relationships
        $this->assertTrue($article->category->is($category));
        $this->assertTrue($article->issue->is($issue));
        $this->assertTrue($article->author->is($author));
        $this->assertTrue($article->artistProfile->is($artist));

        // Test inverse hasMany
        $this->assertCount(1, $author->articles);
        $this->assertCount(1, $issue->articles);
        $this->assertCount(1, $category->articles);
        $this->assertCount(1, $artist->articles);

        // Test published scope
        $this->assertCount(1, Article::published()->get());

        // Test nullOnDelete on issue deletion
        $issue->delete();
        $article->refresh();
        $this->assertNull($article->issue_id);

        // Test nullOnDelete on artist deletion
        $artist->delete();
        $article->refresh();
        $this->assertNull($article->artist_profile_id);
    }

    public function test_media_gallery_casts_and_scopes(): void
    {
        $media = MediaGallery::create([
            'title' => 'Tripoli Concert Recording',
            'slug' => 'tripoli-concert-recording',
            'type' => MediaType::Audio,
            'file_path' => 'audio/tripoli.mp3',
            'duration_seconds' => 320,
            'size_bytes' => 12500000,
        ]);

        $this->assertSame(MediaType::Audio, $media->type);
        $this->assertSame(320, $media->duration_seconds);
        $this->assertSame(12500000, $media->size_bytes);
        $this->assertCount(1, MediaGallery::ofType(MediaType::Audio)->get());
        $this->assertCount(0, MediaGallery::ofType(MediaType::Video)->get());
    }
}
