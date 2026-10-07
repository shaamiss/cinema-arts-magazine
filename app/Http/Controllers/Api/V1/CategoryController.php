<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Services\MagazineCacheService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of root categories with recursive children.
     */
    public function index(): JsonResponse
    {
        $categories = MagazineCacheService::remember('categories', 'categories:tree', 3600, function () {
            return Category::root()
                ->with(['children' => fn ($query) => $query->withCount('articles')])
                ->withCount('articles')
                ->orderBy('name')
                ->get();
        });

        return ApiResponse::success(CategoryResource::collection($categories));
    }
}
