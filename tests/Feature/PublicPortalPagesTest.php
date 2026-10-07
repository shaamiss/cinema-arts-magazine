<?php

namespace Tests\Feature;

use App\Enums\ArtCategory;
use App\Enums\MediaType;
use App\Models\Article;
use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Issue;
use App\Models\MediaGallery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPortalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_home_page_returns_200_and_loads_latest_issue_and_featured_articles(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Cinema', 'slug' => 'cinema']);

        $issue1 = Issue::factory()->published()->create([
            'issue_number' => 1,
            'title' => 'Issue 1 Heritage',
            'slug' => 'issue-1-heritage',
        ]);

        $issue2 = Issue::factory()->published()->create([
            'issue_number' => 2,
            'title' => 'Issue 2 Modernity',
            'slug' => 'issue-2-modernity',
        ]);

        $featuredArticle = Article::factory()->published()->create([
            'category_id' => $category->id,
            'issue_id' => $issue2->id,
            'author_id' => $author->id,
            'title' => 'The Renaissance of Libyan Cinema',
            'slug' => 'renaissance-libyan-cinema',
            'views_count' => 500,
        ]);

        $artist = ArtistProfile::factory()->create([
            'full_name' => 'Mohamed Zwawi',
            'slug' => 'mohamed-zwawi',
            'art_category' => ArtCategory::FineArts,
            'is_featured' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('latestIssue')
            ->where('latestIssue.id', $issue2->id)
            ->where('latestIssue.issue_number', 2)
            ->has('featuredArticles', 1)
            ->where('featuredArticles.0.title', 'The Renaissance of Libyan Cinema')
            ->has('categorizedArticles')
            ->has('spotlightArtists')
        );
    }

    public function test_article_show_renders_view_and_atomically_increments_views_count(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Theatre', 'slug' => 'theatre']);
        $issue = Issue::factory()->published()->create();

        $article = Article::factory()->published()->create([
            'category_id' => $category->id,
            'issue_id' => $issue->id,
            'author_id' => $author->id,
            'title' => 'Dramaturgy in Contemporary Tripoli',
            'slug' => 'dramaturgy-contemporary-tripoli',
            'views_count' => 10,
        ]);

        $this->assertSame(10, $article->fresh()->views_count);

        $response = $this->get(route('articles.show', ['slug' => $article->slug]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Articles/Show')
            ->has('article')
            ->where('article.id', $article->id)
            ->where('article.title', 'Dramaturgy in Contemporary Tripoli')
            ->has('relatedArticles')
        );

        // Assert atomic increment
        $this->assertSame(11, $article->fresh()->views_count);

        // Call again to verify subsequent atomic increments
        $this->get(route('articles.show', ['slug' => $article->slug]))->assertOk();
        $this->assertSame(12, $article->fresh()->views_count);
    }

    public function test_issue_show_renders_with_page_urls_for_the_reader(): void
    {
        $issue = Issue::factory()->published()->create([
            'issue_number' => 5,
            'title' => 'Special Issue: National Archives',
            'slug' => 'issue-5-national-archives',
            'pages_count' => 4,
        ]);

        $response = $this->get(route('issues.show', ['slug' => $issue->slug]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Issues/Show')
            ->has('issue')
            ->where('issue.id', $issue->id)
            ->where('issue.issue_number', 5)
            ->has('readerData')
            ->has('readerData.pages', 4)
            ->has('readerData.thumbnails', 4)
            ->where('readerData.issue.pages_count', 4)
        );
    }

    public function test_issues_archive_page_renders_with_pagination_and_year_filter(): void
    {
        Issue::factory()->published()->create([
            'issue_number' => 1,
            'published_at' => '2025-06-15',
        ]);

        Issue::factory()->published()->create([
            'issue_number' => 2,
            'published_at' => '2026-02-10',
        ]);

        $response = $this->get(route('issues.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Issues/Index')
            ->has('issues.data', 2)
            ->has('availableYears')
        );

        $filteredResponse = $this->get(route('issues.index', ['year' => 2026]));
        $filteredResponse->assertOk();
        $filteredResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Issues/Index')
            ->has('issues.data', 1)
            ->where('currentYear', 2026)
        );
    }

    public function test_artists_index_and_show_pages_render_correctly(): void
    {
        $artist = ArtistProfile::factory()->create([
            'full_name' => 'Ali Ahmed Bakathir',
            'slug' => 'ali-ahmed-bakathir',
            'art_category' => ArtCategory::Theatre,
        ]);

        $indexResponse = $this->get(route('artists.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Artists/Index')
            ->has('artists.data', 1)
            ->where('artists.data.0.slug', 'ali-ahmed-bakathir')
        );

        $showResponse = $this->get(route('artists.show', ['slug' => $artist->slug]));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Public/Artists/Show')
            ->has('artist')
            ->where('artist.slug', 'ali-ahmed-bakathir')
            ->has('articles')
        );
    }

    public function test_gallery_index_page_renders_media_items(): void
    {
        MediaGallery::factory()->create([
            'title' => 'Opening Gala of Tripoli Arts',
            'slug' => 'opening-gala-tripoli-arts',
            'type' => MediaType::Image,
        ]);

        $response = $this->get(route('gallery.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Gallery/Index')
            ->has('media.data', 1)
            ->where('media.data.0.slug', 'opening-gala-tripoli-arts')
        );
    }

    public function test_instant_search_endpoint_returns_json_results(): void
    {
        Article::factory()->published()->create([
            'title' => 'Cinematic Evolution in Libya',
        ]);

        Issue::factory()->published()->create([
            'title' => 'Cinema and Theater Review',
        ]);

        ArtistProfile::factory()->create([
            'full_name' => 'Cinema Pioneer Artist',
        ]);

        $response = $this->getJson(route('api.search', ['q' => 'Cinema']));

        $response->assertOk()
            ->assertJsonStructure([
                'articles',
                'issues',
                'artists',
            ]);

        $this->assertCount(1, $response->json('articles'));
        $this->assertCount(1, $response->json('issues'));
        $this->assertCount(1, $response->json('artists'));
    }
}
