<?php

namespace Tests\Feature\Api\V1;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DigitalMagazineApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_articles_endpoint_returns_standard_envelope_and_only_published_content(): void
    {
        $category = Category::factory()->create();

        $publishedArticle = Article::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Published Cultural Report',
        ]);

        $draftArticle = Article::factory()->create([
            'category_id' => $category->id,
            'status' => PublicationStatus::Draft,
            'title' => 'Unpublished Internal Draft',
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'status',
                        'views_count',
                        'published_at',
                        'category',
                        'author',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Cultural Report');
    }

    public function test_article_show_increments_views_count_atomically(): void
    {
        $article = Article::factory()->published()->create([
            'views_count' => 42,
        ]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $article->slug);

        $this->assertSame(43, (int) $article->refresh()->views_count);
    }

    public function test_caching_and_invalidation_on_admin_article_update(): void
    {
        $chiefEditor = User::factory()->create();
        $chiefEditor->assignRole('chief-editor');

        $category = Category::factory()->create();
        $article = Article::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Original Cached Title',
        ]);

        // First public request populates cache
        $firstResponse = $this->getJson('/api/v1/articles');
        $firstResponse->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Original Cached Title');

        // Admin updates article
        Sanctum::actingAs($chiefEditor);
        $updateResponse = $this->putJson("/api/v1/admin/articles/{$article->id}", [
            'title' => 'Updated Fresh Title',
            'content' => 'Updated content text here.',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Next public request should reflect invalidated fresh cache
        $secondResponse = $this->getJson('/api/v1/articles');
        $secondResponse->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Updated Fresh Title');
    }

    public function test_section_editor_cannot_publish_article_via_status_endpoint(): void
    {
        $sectionEditor = User::factory()->create();
        $sectionEditor->assignRole('section-editor');

        $category = Category::factory()->create();
        $sectionEditor->managedCategories()->attach($category);

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'status' => PublicationStatus::Review,
        ]);

        Sanctum::actingAs($sectionEditor);

        $response = $this->patchJson("/api/v1/admin/articles/{$article->id}/status", [
            'status' => 'published',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertSame(PublicationStatus::Review, $article->refresh()->status);
    }

    public function test_chief_editor_can_publish_article_and_issue(): void
    {
        $chiefEditor = User::factory()->create();
        $chiefEditor->assignRole('chief-editor');

        $category = Category::factory()->create();
        $issue = Issue::factory()->create(['status' => PublicationStatus::Review]);
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'issue_id' => $issue->id,
            'status' => PublicationStatus::Review,
        ]);

        Sanctum::actingAs($chiefEditor);

        // Publish article
        $articleResponse = $this->patchJson("/api/v1/admin/articles/{$article->id}/status", [
            'status' => 'published',
        ]);

        $articleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published');

        $this->assertSame(PublicationStatus::Published, $article->refresh()->status);

        // Publish issue
        $issueResponse = $this->patchJson("/api/v1/admin/issues/{$issue->id}/publish");

        $issueResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published');

        $this->assertSame(PublicationStatus::Published, $issue->refresh()->status);
    }

    public function test_public_issues_index_returns_published_issues_only(): void
    {
        $publishedIssue = Issue::factory()->published()->create([
            'issue_number' => 101,
        ]);

        $draftIssue = Issue::factory()->create([
            'issue_number' => 102,
            'status' => PublicationStatus::Draft,
        ]);

        $response = $this->getJson('/api/v1/issues');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.issue_number', 101);
    }

    public function test_public_categories_index_returns_cached_tree(): void
    {
        $parent = Category::factory()->create(['name' => 'Cinema', 'slug' => 'cinema', 'parent_id' => null]);
        Category::factory()->create(['name' => 'Documentary Cinema', 'slug' => 'documentary-cinema', 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.children.0.name', 'Documentary Cinema');
    }

    public function test_admin_media_upload_stores_file_and_extracts_metadata(): void
    {
        Storage::fake('public');

        $mediaProducer = User::factory()->create();
        $mediaProducer->assignRole('media-producer');

        Sanctum::actingAs($mediaProducer);

        $fakeFile = UploadedFile::fake()->image('stage_photo.jpg', 600, 400)->size(500);

        $response = $this->postJson('/api/v1/admin/media/upload', [
            'title' => 'Tripoli Stage Rehearsal',
            'file' => $fakeFile,
            'description' => 'Photo rehearsal from 2026 festival.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.title', 'Tripoli Stage Rehearsal');

        $this->assertDatabaseHas('media_galleries', [
            'title' => 'Tripoli Stage Rehearsal',
            'type' => 'image',
        ]);
    }
}
