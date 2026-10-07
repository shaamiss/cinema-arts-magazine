<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use App\Models\MediaGallery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_section_editor_cannot_publish_an_article_directly(): void
    {
        $sectionEditor = User::factory()->create();
        $sectionEditor->assignRole('section-editor');

        $category = Category::factory()->create();
        $sectionEditor->managedCategories()->attach($category);

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'status' => PublicationStatus::Review,
        ]);

        $this->assertTrue($sectionEditor->isSectionEditor());
        $this->assertFalse($sectionEditor->can('publish', $article));
        $this->assertTrue($sectionEditor->cannot('publish', $article));
    }

    public function test_section_editor_cannot_edit_articles_outside_their_assigned_categories(): void
    {
        $sectionEditor = User::factory()->create();
        $sectionEditor->assignRole('section-editor');

        $cinemaCategory = Category::factory()->create(['name' => 'Cinema', 'slug' => 'cinema']);
        $theatreCategory = Category::factory()->create(['name' => 'Theatre', 'slug' => 'theatre']);

        // Assign only Cinema to this editor
        $sectionEditor->managedCategories()->attach($cinemaCategory);

        $cinemaArticle = Article::factory()->create([
            'category_id' => $cinemaCategory->id,
            'status' => PublicationStatus::Draft,
        ]);

        $theatreArticle = Article::factory()->create([
            'category_id' => $theatreCategory->id,
            'status' => PublicationStatus::Draft,
        ]);

        $this->assertTrue($sectionEditor->canManageCategory($cinemaCategory));
        $this->assertFalse($sectionEditor->canManageCategory($theatreCategory));

        // Can update article within assigned category
        $this->assertTrue($sectionEditor->can('update', $cinemaArticle));

        // Cannot update article outside assigned category
        $this->assertFalse($sectionEditor->can('update', $theatreArticle));
    }

    public function test_section_editor_cannot_edit_published_article_even_in_their_category(): void
    {
        $sectionEditor = User::factory()->create();
        $sectionEditor->assignRole('section-editor');

        $category = Category::factory()->create();
        $sectionEditor->managedCategories()->attach($category);

        $publishedArticle = Article::factory()->published()->create([
            'category_id' => $category->id,
        ]);

        $this->assertFalse($sectionEditor->can('update', $publishedArticle));
    }

    public function test_chief_editor_can_publish_issues_and_articles(): void
    {
        $chiefEditor = User::factory()->create();
        $chiefEditor->assignRole('chief-editor');

        $category = Category::factory()->create();
        $issue = Issue::factory()->create([
            'status' => PublicationStatus::Review,
        ]);

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'issue_id' => $issue->id,
            'status' => PublicationStatus::Review,
        ]);

        $this->assertTrue($chiefEditor->isChiefEditor());
        $this->assertTrue($chiefEditor->can('publish', $article));
        $this->assertTrue($chiefEditor->can('publish', $issue));
        $this->assertTrue($chiefEditor->can('update', $article));
        $this->assertTrue($chiefEditor->can('update', $issue));
    }

    public function test_super_admin_bypasses_all_checks_via_gate_before(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $category = Category::factory()->create();
        $article = Article::factory()->published()->create([
            'category_id' => $category->id,
        ]);
        $issue = Issue::factory()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());

        // Bypasses policy rules
        $this->assertTrue($superAdmin->can('publish', $article));
        $this->assertTrue($superAdmin->can('update', $article));
        $this->assertTrue($superAdmin->can('delete', $article));

        $this->assertTrue($superAdmin->can('publish', $issue));
        $this->assertTrue($superAdmin->can('update', $issue));
        $this->assertTrue($superAdmin->can('delete', $issue));

        // Bypasses permissions not explicitly granted to any role
        $this->assertTrue($superAdmin->can('system.manage-settings'));
        $this->assertTrue($superAdmin->can('system.manage-users'));
        $this->assertTrue($superAdmin->can('any-undefined-arbitrary-ability'));
    }

    public function test_media_producer_can_upload_and_delete_media_but_cannot_publish_articles(): void
    {
        $mediaProducer = User::factory()->create();
        $mediaProducer->assignRole('media-producer');

        $media = MediaGallery::factory()->create();
        $article = Article::factory()->create();

        $this->assertTrue($mediaProducer->isMediaProducer());
        $this->assertTrue($mediaProducer->can('upload', MediaGallery::class));
        $this->assertTrue($mediaProducer->can('delete', $media));

        $this->assertFalse($mediaProducer->can('publish', $article));
        $this->assertFalse($mediaProducer->can('articles.publish'));
    }
}
