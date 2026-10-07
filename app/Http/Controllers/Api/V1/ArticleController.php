<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use App\Services\MagazineCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a paginated listing of published articles with dynamic filters.
     */
    public function index(Request $request): JsonResponse
    {
        $categorySlug = $request->query('category_slug');
        $issueId = $request->query('issue_id');
        $artistSlug = $request->query('artist_slug');
        $search = $request->query('search');
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 15), 50);

        $cacheKey = sprintf(
            'articles:index:cat=%s:issue=%s:art=%s:q=%s:p=%d:pp=%d',
            $categorySlug ?? 'all',
            $issueId ?? 'all',
            $artistSlug ?? 'all',
            $search ? md5($search) : 'none',
            $page,
            $perPage
        );

        $paginated = MagazineCacheService::remember('articles', $cacheKey, 1800, function () use ($categorySlug, $issueId, $artistSlug, $search, $perPage) {
            return Article::published()
                ->with(['category', 'author:id,name', 'issue:id,title,slug,issue_number', 'artistProfile:id,full_name,slug,art_category'])
                ->when($categorySlug, function ($query, $slug) {
                    $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
                })
                ->when($issueId, fn ($query, $id) => $query->where('issue_id', $id))
                ->when($artistSlug, function ($query, $slug) {
                    $query->whereHas('artistProfile', fn ($q) => $q->where('slug', $slug));
                })
                ->when($search, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('title', 'like', "%{$term}%")
                            ->orWhere('excerpt', 'like', "%{$term}%");
                    });
                })
                ->latest('published_at')
                ->paginate($perPage);
        });

        return ApiResponse::success(ArticleListResource::collection($paginated));
    }

    /**
     * Display the specified published article by slug with atomic views count increment.
     */
    public function show(string $slug): JsonResponse
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->with(['category', 'author:id,name', 'issue', 'artistProfile'])
            ->firstOrFail();

        // Increment views_count atomically without updating timestamps
        $article->incrementQuietly('views_count');

        return ApiResponse::success(new ArticleDetailResource($article));
    }
}
