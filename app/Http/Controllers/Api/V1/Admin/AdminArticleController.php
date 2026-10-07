<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreArticleRequest;
use App\Http\Requests\Api\V1\UpdateArticleRequest;
use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use App\Services\MagazineCacheService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminArticleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of articles scoped to the authenticated user's permissions and categories.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Article::class);

        $user = $request->user();
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $perPage = min((int) $request->query('per_page', 20), 100);

        $articles = Article::query()
            ->with(['category', 'author:id,name', 'issue:id,title,issue_number', 'artistProfile:id,full_name,slug'])
            // Scoping based on user role
            ->when(! $user->isSuperAdmin() && ! $user->isChiefEditor(), function ($query) use ($user) {
                if ($user->isSectionEditor()) {
                    $categoryIds = $user->managedCategories()->pluck('categories.id')->all();
                    $query->whereIn('category_id', $categoryIds);
                } else {
                    $query->where('author_id', $user->id);
                }
            })
            ->when($status, fn ($query, $st) => $query->where('status', $st))
            ->when($categoryId, fn ($query, $catId) => $query->where('category_id', $catId))
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('excerpt', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return ApiResponse::success(ArticleListResource::collection($articles));
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        }

        $data['author_id'] = $request->user()->id;

        // Default to Draft if not specified
        if (empty($data['status'])) {
            $data['status'] = PublicationStatus::Draft;
        }

        // Section editors cannot create directly in Published status without publish permission
        if ($data['status'] === PublicationStatus::Published && ! $request->user()->can('publish', Article::class)) {
            $data['status'] = PublicationStatus::Draft;
        }

        $article = Article::create($data);

        MagazineCacheService::flush(['articles', 'issues']);

        return ApiResponse::success(
            new ArticleDetailResource($article->load(['category', 'author:id,name', 'issue', 'artistProfile'])),
            'Article created successfully.',
            201
        );
    }

    /**
     * Display the specified article.
     */
    public function show(Article $article): JsonResponse
    {
        $this->authorize('view', $article);

        return ApiResponse::success(
            new ArticleDetailResource($article->load(['category', 'author:id,name', 'issue', 'artistProfile']))
        );
    }

    /**
     * Update the specified article.
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $data = $request->validated();

        // If user is attempting to publish directly, ensure they have publish permission
        if (isset($data['status']) && $data['status'] === PublicationStatus::Published->value && ! $request->user()->can('publish', $article)) {
            unset($data['status']);
        }

        $article->update($data);

        MagazineCacheService::flush(['articles', 'issues']);

        return ApiResponse::success(
            new ArticleDetailResource($article->fresh(['category', 'author:id,name', 'issue', 'artistProfile'])),
            'Article updated successfully.'
        );
    }

    /**
     * Dedicated state transition endpoint for publishing or submitting articles for review.
     */
    public function updateStatus(Request $request, Article $article): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(PublicationStatus::class)],
        ]);

        $newStatus = PublicationStatus::from($validated['status']);

        match ($newStatus) {
            PublicationStatus::Review => $this->authorize('submitReview', $article),
            PublicationStatus::Published, PublicationStatus::Archived => $this->authorize('publish', $article),
            PublicationStatus::Draft => $this->authorize('update', $article),
        };

        $attributes = ['status' => $newStatus];
        if ($newStatus === PublicationStatus::Published && ! $article->published_at) {
            $attributes['published_at'] = now();
        }

        $article->update($attributes);

        MagazineCacheService::flush(['articles', 'issues']);

        return ApiResponse::success(
            new ArticleDetailResource($article->fresh(['category', 'author:id,name', 'issue', 'artistProfile'])),
            "Article status changed to {$newStatus->value}."
        );
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        MagazineCacheService::flush(['articles', 'issues']);

        return ApiResponse::success(null, 'Article deleted successfully.');
    }
}
