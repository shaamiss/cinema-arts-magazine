<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Events\IssuePagesProcessedEvent;
use App\Jobs\OptimizeMediaImageJob;
use App\Jobs\ProcessPdfPagesJob;
use App\Models\Issue;
use App\Models\MediaGallery;
use App\Services\MagazineCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssuePdfProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_pdf_pages_job_updates_pages_count_and_generates_webp_pages(): void
    {
        Storage::fake('public');
        Event::fake([IssuePagesProcessedEvent::class]);

        $issue = Issue::factory()->create([
            'pages_count' => 0,
            'pdf_file_path' => 'issues/test_document.pdf',
        ]);

        // Create a minimal PDF file with 4 pages marker
        $mockPdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            ."2 0 obj\n<< /Type /Pages /Count 4 /Kids [3 0 R 4 0 R 5 0 R 6 0 R] >>\nendobj\n"
            ."3 0 obj\n<< /Type /Page >>\nendobj\n"
            ."4 0 obj\n<< /Type /Page >>\nendobj\n"
            ."5 0 obj\n<< /Type /Page >>\nendobj\n"
            ."6 0 obj\n<< /Type /Page >>\nendobj\n%%EOF";

        Storage::disk('public')->put('issues/test_document.pdf', $mockPdfContent);

        // Prepopulate cache to verify invalidation
        MagazineCacheService::remember('issues', 'issues:cache_test', 3600, fn () => 'cached_value');

        // Run Job synchronously
        (new ProcessPdfPagesJob($issue))->handle();

        // 1. Verify pages_count updated
        $this->assertSame(4, (int) $issue->refresh()->pages_count);

        // 2. Verify WebP pages and thumbnail generated
        $this->assertTrue(Storage::disk('public')->exists("issues/{$issue->id}/pages/page_1.webp"));
        $this->assertTrue(Storage::disk('public')->exists("issues/{$issue->id}/pages/page_2.webp"));
        $this->assertTrue(Storage::disk('public')->exists("issues/{$issue->id}/pages/page_3.webp"));
        $this->assertTrue(Storage::disk('public')->exists("issues/{$issue->id}/pages/page_4.webp"));
        $this->assertTrue(Storage::disk('public')->exists("issues/{$issue->id}/pages/page_1_thumb.webp"));

        // 3. Verify event dispatched
        Event::assertDispatched(IssuePagesProcessedEvent::class, function ($event) use ($issue) {
            return $event->issue->id === $issue->id && $event->pagesCount === 4;
        });
    }

    public function test_issues_pages_endpoint_returns_ordered_page_urls(): void
    {
        Storage::fake('public');

        $issue = Issue::factory()->published()->create([
            'pages_count' => 5,
        ]);

        $response = $this->getJson("/api/v1/issues/{$issue->slug}/pages");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data.pages')
            ->assertJsonCount(5, 'data.thumbnails')
            ->assertJsonPath('data.issue.id', $issue->id)
            ->assertJsonPath('data.issue.pages_count', 5);
    }

    public function test_optimize_media_image_job_converts_image_to_webp(): void
    {
        Storage::fake('public');

        // Create a test GD image
        $image = imagecreatetruecolor(200, 200);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 200, 200, $white);
        ob_start();
        imagejpeg($image);
        $jpegData = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put('media/painting.jpg', $jpegData);

        $media = MediaGallery::create([
            'title' => 'Painting Showcase',
            'slug' => 'painting-showcase',
            'type' => MediaType::Image,
            'file_path' => 'media/painting.jpg',
            'size_bytes' => strlen($jpegData),
        ]);

        (new OptimizeMediaImageJob($media))->handle();

        $media->refresh();
        $this->assertStringEndsWith('.webp', $media->file_path);
        $this->assertTrue(Storage::disk('public')->exists($media->file_path));
        $this->assertGreaterThan(0, $media->size_bytes);
    }
}
