<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueDetailResource;
use App\Http\Resources\IssueResource;
use App\Http\Responses\ApiResponse;
use App\Models\Issue;
use App\Services\MagazineCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IssueController extends Controller
{
    /**
     * Display a paginated listing of published magazine issues.
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->query('year');
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 12), 50);

        $cacheKey = "issues:index:year={$year}:page={$page}:per_page={$perPage}";

        $paginated = MagazineCacheService::remember('issues', $cacheKey, 3600, function () use ($year, $perPage) {
            return Issue::published()
                ->when($year, fn ($query) => $query->whereYear('published_at', $year))
                ->withCount(['articles' => fn ($query) => $query->published()])
                ->orderByDesc('issue_number')
                ->paginate($perPage);
        });

        return ApiResponse::success(IssueResource::collection($paginated));
    }

    /**
     * Display the specified published issue by slug with its published articles.
     */
    public function show(string $slug): JsonResponse
    {
        $issue = Issue::published()
            ->where('slug', $slug)
            ->with(['articles' => function ($query) {
                $query->published()
                    ->with(['category', 'author:id,name', 'artistProfile'])
                    ->latest('published_at');
            }])
            ->firstOrFail();

        return ApiResponse::success(new IssueDetailResource($issue));
    }

    /**
     * Get rendered page image URLs for the issue flipbook reader.
     */
    public function pages(string $slug): JsonResponse
    {
        $issue = Issue::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $disk = Storage::disk('public');
        $pagesDir = "issues/{$issue->id}/pages";
        $pages = [];
        $thumbnails = [];

        $count = $issue->pages_count > 0 ? $issue->pages_count : 1;

        for ($i = 1; $i <= $count; $i++) {
            $pagePath = "{$pagesDir}/page_{$i}.webp";
            $pages[] = $disk->url($pagePath);

            $thumbPath = "{$pagesDir}/page_{$i}_thumb.webp";
            $thumbnails[] = $disk->exists($thumbPath) ? $disk->url($thumbPath) : $disk->url($pagePath);
        }

        return ApiResponse::success([
            'issue' => [
                'id' => $issue->id,
                'title' => $issue->title,
                'issue_number' => $issue->issue_number,
                'slug' => $issue->slug,
                'pages_count' => count($pages),
                'pdf_url' => $issue->pdf_file_path ? $disk->url($issue->pdf_file_path) : null,
            ],
            'pages' => $pages,
            'thumbnails' => $thumbnails,
        ]);
    }
}
