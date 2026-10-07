<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistProfileResource;
use App\Http\Responses\ApiResponse;
use App\Models\ArtistProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtistProfileController extends Controller
{
    /**
     * Display a paginated listing of artist profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('art_category');
        $search = $request->query('search');
        $perPage = min((int) $request->query('per_page', 12), 50);

        $artists = ArtistProfile::query()
            ->when($category, fn ($query) => $query->where('art_category', $category))
            ->when($search, fn ($query, $name) => $query->where('full_name', 'like', "%{$name}%"))
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->orderBy('full_name')
            ->paginate($perPage);

        return ApiResponse::success(ArtistProfileResource::collection($artists));
    }

    /**
     * Display the specified artist profile by slug with recent published articles.
     */
    public function show(string $slug): JsonResponse
    {
        $artist = ArtistProfile::where('slug', $slug)
            ->with(['articles' => function ($query) {
                $query->published()
                    ->with(['category', 'author:id,name'])
                    ->latest('published_at')
                    ->take(10);
            }])
            ->withCount(['articles' => fn ($query) => $query->published()])
            ->firstOrFail();

        return ApiResponse::success(new ArtistProfileResource($artist));
    }
}
