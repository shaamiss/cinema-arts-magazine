<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UploadMediaRequest;
use App\Http\Resources\MediaGalleryResource;
use App\Http\Responses\ApiResponse;
use App\Models\MediaGallery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a paginated listing of media items.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaGallery::class);

        $type = $request->query('type');
        $perPage = min((int) $request->query('per_page', 24), 100);

        $media = MediaGallery::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(MediaGalleryResource::collection($media));
    }

    /**
     * Upload a new media file, extract metadata, and persist the record.
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $title = $request->input('title');
        $slug = $request->input('slug') ?: Str::slug($title).'-'.Str::lower(Str::random(5));
        $description = $request->input('description');

        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $sizeBytes = $file->getSize();

        // Determine MediaType from mime type
        $type = match (true) {
            str_starts_with($mimeType, 'image/') => MediaType::Image,
            str_starts_with($mimeType, 'audio/') => MediaType::Audio,
            str_starts_with($mimeType, 'video/') => MediaType::Video,
            $mimeType === 'application/pdf' || str_ends_with(strtolower($file->getClientOriginalName()), '.pdf') => MediaType::Pdf,
            default => MediaType::Image,
        };

        // Store file in public storage
        $storedPath = $file->store('media', 'public');

        $thumbnailPath = null;
        if ($type === MediaType::Image) {
            $thumbnailPath = $storedPath;
        }

        $media = MediaGallery::create([
            'title' => $title,
            'slug' => $slug,
            'type' => $type,
            'file_path' => $storedPath,
            'thumbnail_path' => $thumbnailPath,
            'duration_seconds' => null,
            'size_bytes' => $sizeBytes,
            'description' => $description,
        ]);

        return ApiResponse::success(
            new MediaGalleryResource($media),
            'Media uploaded successfully.',
            201
        );
    }

    /**
     * Remove the specified media item from storage and database.
     */
    public function destroy(MediaGallery $mediaGallery): JsonResponse
    {
        $this->authorize('delete', $mediaGallery);

        if (Storage::disk('public')->exists($mediaGallery->file_path)) {
            Storage::disk('public')->delete($mediaGallery->file_path);
        }

        $mediaGallery->delete();

        return ApiResponse::success(null, 'Media item deleted successfully.');
    }
}
