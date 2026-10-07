<?php

namespace App\Jobs;

use App\Enums\MediaType;
use App\Models\MediaGallery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OptimizeMediaImageJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public MediaGallery $mediaGallery)
    {
        $this->onQueue('media-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->mediaGallery->type !== MediaType::Image) {
            return;
        }

        $disk = Storage::disk('public');
        $originalPath = $this->mediaGallery->file_path;

        if (! $disk->exists($originalPath)) {
            Log::warning("Media file not found for optimization: {$originalPath}");

            return;
        }

        $imageContents = $disk->get($originalPath);
        $sourceImage = @imagecreatefromstring($imageContents);

        if (! $sourceImage) {
            Log::warning("Could not create GD image from media: {$originalPath}");

            return;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        // Cap dimensions at 2400px width max
        $maxDimension = 2400;
        if ($width > $maxDimension) {
            $newWidth = $maxDimension;
            $newHeight = (int) ($height * ($maxDimension / $width));
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);

            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($sourceImage);
            $sourceImage = $resizedImage;
        }

        // Convert to WebP buffer (stripping EXIF metadata automatically)
        ob_start();
        imagewebp($sourceImage, null, 82);
        $webpData = ob_get_clean();
        imagedestroy($sourceImage);

        // Determine destination WebP path
        $pathInfo = pathinfo($originalPath);
        $webpPath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '').$pathInfo['filename'].'.webp';

        // Save WebP file
        $disk->put($webpPath, $webpData);

        // If path changed, remove original image to save disk space
        if ($webpPath !== $originalPath && $disk->exists($originalPath)) {
            $disk->delete($originalPath);
        }

        $newSizeBytes = strlen($webpData);

        $this->mediaGallery->update([
            'file_path' => $webpPath,
            'size_bytes' => $newSizeBytes,
        ]);

        Log::info("Optimized Media ID {$this->mediaGallery->id} to WebP. New size: {$newSizeBytes} bytes.");
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("OptimizeMediaImageJob failed for Media ID {$this->mediaGallery->id}: {$exception->getMessage()}");
    }
}
