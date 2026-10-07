<?php

use App\Http\Controllers\Api\V1\Admin\AdminArticleController;
use App\Http\Controllers\Api\V1\Admin\AdminIssueController;
use App\Http\Controllers\Api\V1\Admin\AdminMediaController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\ArtistProfileController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\IssueController;
use App\Http\Controllers\Api\V1\MediaGalleryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public API Group (Rate limited: 60 requests/min)
    Route::middleware('throttle:60,1')->group(function () {
        // Issues
        Route::get('/issues', [IssueController::class, 'index'])->name('api.v1.issues.index');
        Route::get('/issues/{slug}', [IssueController::class, 'show'])->name('api.v1.issues.show');
        Route::get('/issues/{slug}/pages', [IssueController::class, 'pages'])->name('api.v1.issues.pages');

        // Articles
        Route::get('/articles', [ArticleController::class, 'index'])->name('api.v1.articles.index');
        Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('api.v1.articles.show');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');

        // Artist Profiles
        Route::get('/artists', [ArtistProfileController::class, 'index'])->name('api.v1.artists.index');
        Route::get('/artists/{slug}', [ArtistProfileController::class, 'show'])->name('api.v1.artists.show');

        // Media Gallery
        Route::get('/media', [MediaGalleryController::class, 'index'])->name('api.v1.media.index');
    });

    // Admin CMS API Group (auth:sanctum, Rate limited: 120 requests/min)
    Route::prefix('admin')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ],
            ]);
        })->name('api.v1.admin.user');

        // Admin Articles
        Route::get('/articles', [AdminArticleController::class, 'index'])->name('api.v1.admin.articles.index');
        Route::post('/articles', [AdminArticleController::class, 'store'])->name('api.v1.admin.articles.store');
        Route::get('/articles/{article}', [AdminArticleController::class, 'show'])->name('api.v1.admin.articles.show');
        Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('api.v1.admin.articles.update');
        Route::patch('/articles/{article}/status', [AdminArticleController::class, 'updateStatus'])->name('api.v1.admin.articles.update-status');
        Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('api.v1.admin.articles.destroy');

        // Admin Issues
        Route::get('/issues', [AdminIssueController::class, 'index'])->name('api.v1.admin.issues.index');
        Route::post('/issues', [AdminIssueController::class, 'store'])->name('api.v1.admin.issues.store');
        Route::get('/issues/{issue}', [AdminIssueController::class, 'show'])->name('api.v1.admin.issues.show');
        Route::put('/issues/{issue}', [AdminIssueController::class, 'update'])->name('api.v1.admin.issues.update');
        Route::patch('/issues/{issue}/publish', [AdminIssueController::class, 'publish'])->name('api.v1.admin.issues.publish');
        Route::delete('/issues/{issue}', [AdminIssueController::class, 'destroy'])->name('api.v1.admin.issues.destroy');

        // Admin Media
        Route::get('/media', [AdminMediaController::class, 'index'])->name('api.v1.admin.media.index');
        Route::post('/media/upload', [AdminMediaController::class, 'upload'])->name('api.v1.admin.media.upload');
        Route::delete('/media/{mediaGallery}', [AdminMediaController::class, 'destroy'])->name('api.v1.admin.media.destroy');
    });
});
