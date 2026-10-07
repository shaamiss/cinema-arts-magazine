<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaGalleryResource;
use App\Http\Responses\ApiResponse;
use App\Models\MediaGallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaGalleryController extends Controller
{
    /**
     * Display a paginated listing of media gallery items.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $perPage = min((int) $request->query('per_page', 18), 60);

        $media = MediaGallery::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(MediaGalleryResource::collection($media));
    }
}
