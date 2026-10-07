<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_regular_user_without_editorial_role_is_forbidden(): void
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get('/admin');

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_filament_dashboard_and_users(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/admin');
        $response->assertOk();

        $usersResponse = $this->actingAs($superAdmin)->get('/admin/users');
        $usersResponse->assertOk();
    }

    public function test_chief_editor_can_access_filament_dashboard_but_not_user_management(): void
    {
        $chiefEditor = User::factory()->create();
        $chiefEditor->assignRole('chief-editor');

        $response = $this->actingAs($chiefEditor)->get('/admin');
        $response->assertOk();

        $usersResponse = $this->actingAs($chiefEditor)->get('/admin/users');
        $usersResponse->assertForbidden();
    }

    public function test_section_editor_can_access_filament_articles_and_dashboard(): void
    {
        $sectionEditor = User::factory()->create();
        $sectionEditor->assignRole('section-editor');

        $category = Category::factory()->create(['name' => 'مسرح', 'slug' => 'theatre']);
        $sectionEditor->managedCategories()->attach($category);

        $response = $this->actingAs($sectionEditor)->get('/admin');
        $response->assertOk();

        $articlesResponse = $this->actingAs($sectionEditor)->get('/admin/articles');
        $articlesResponse->assertOk();

        $usersResponse = $this->actingAs($sectionEditor)->get('/admin/users');
        $usersResponse->assertForbidden();
    }

    public function test_admin_panel_sets_arabic_locale(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)->get('/admin');

        $this->assertEquals('ar', app()->getLocale());
    }
}
